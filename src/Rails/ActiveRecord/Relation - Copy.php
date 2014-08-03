<?php
namespace Rails\ActiveRecord;

use Zend\Db\Sql\Select;
use Zend\Db\Sql\Predicate as ZfPredicate;
use Zend\Paginator\Adapter\DbSelect as Paginator;
use Rails\ActiveRecord\Exception\RecordNotFoundException;
use Rails\ActiveRecord\Associations\Associations;
use Rails\ActiveRecord\Associations\CollectionProxy;
use Rails\ActiveModel\Attributes\Attributes;

class Relation extends Relation\AbstractRelation
{
    /**
     * @var string
     */
    protected $modelClass;
    
    /**
     * Flag to modify query regarding soft-deleted records.
     * Possible values are:
     * * `true`: both deleted and non-deleted records.
     * * `false`: non-deleted records only.
     * * `only`: deleted records only.
     *
     * @var bool|string
     * @see selectDeletedRecords()
     */
    protected $deleted = false;
    
    protected $includesData = [];
    
    public function __construct($modelClass)
    {
        parent::__construct();
        $this->modelClass = $modelClass;
        $this->select->from($modelClass::tableName());
    }
    
    /**
     * Unknown methods are derived to the Collection object, so it's possible to do:
     *
     * ```
     * $users->where('id', '>', 15)->any();
     * $posts->order('title')->toJson();
     * ```
     */
    public function __call($method, $params)
    {
        $this->load();
        return call_user_func_array([$this->records, $method], $params);
    }
    
    public function deleted($value = true)
    {
        $this->deleted = $value;
        return $this;
    }
    
    /**
     * @return Collection
     */
    public function getIterator()
    {
        $this->load();
        return $this->records;
    }
    
    /**
     * Find record with primary key $id. If it's not found,
     * RecordNotFoundException is thrown.
     */
    public function find($id)
    {
        $rel = $this->currentOrClone();
        $modelClass = $this->modelClass;
        $tableName  = $modelClass::tableName();
        $first = $rel->where([$tableName . '.' . $modelClass::primaryKey() => $id])->first();
        if (!$first) {
            throw new RecordNotFoundException(sprintf(
                "Couldn't find %s with %s=%s",
                get_called_class(),
                $modelClass::primaryKey(),
                $id
            ));
        }
        return $first;
    }
    
    public function first($limit = 1)
    {
        $select = clone $this->select;
        $this->orderByIdIfUnordered($select);
        
        if (!$this->includesData) {
            $select->limit($limit);
        }
        
        $rows = $this->loadRecords($select);
        
        if ($rows) {
            if ($limit == 1) {
                return $this->buildSingleModel($rows);
            } else {
                return $this->buildCollection($rows);
            }
        }
        
        return null;
    }
    
    public function take($limit = 1)
    {
        $data = parent::take($limit);
        
        if ($data) {
            if ($limit == 1) {
                return $this->buildSingleModel($data);
            } else {
                return $this->buildCollection($data);
            }
        }
        
        return null;
    }
    
    /**
     * $rel->includes('posts', 'comments');
     * $rel->includes(['posts', 'comments' => 'users']);
     * $rel->includes(['posts', 'comments' => ['users' => etc...]]);
     */
    public function includes(/*...$args*/)
    {
        $rel = $this->currentOrClone();
        
        $args = func_get_args();
        
        if (is_array($args[0])) {
            $args = $args[0];
        }
        
        $assocs = Associations::forClass($rel->modelClass);
        
        $modelClass = $rel->modelClass;
        $ownerTableName = $modelClass::tableName();
        $ownerCols = $rel->buildColumnsArgs($modelClass);
        
        foreach ($args as $key => $arg) {
            if (is_string($arg)) {
                $options = $assocs->get($arg);
                
                if ($options === false) {
                    throw new \Exception(sprintf(
                        "Association %s for class %s doesn't exist",
                        $arg,
                        $modelClass
                    ));
                }
                
                $cols = $rel->buildColumnsArgs($options['className'], $arg, $options['type'], $options['foreignKey']);
                
                $tableName = $options['className']::tableName();
                
                switch ($options['type']) {
                    case 'hasMany':
                        $on = $tableName . '.' . $options['foreignKey']
                                . ' = ' . $ownerTableName . '.' . $modelClass::primaryKey();
                        
                        $rel->join(
                            $tableName,
                            $on,
                            $cols,
                            'left outer'
                        );
                        break;
                    
                    case 'belongsTo':
                        $on = $ownerTableName . '.' . $options['foreignKey']
                                . ' = ' . $tableName . '.' . $modelClass::primaryKey();
                        
                        $rel->join(
                            $tableName,
                            $on,
                            $cols,
                            'left outer'
                        );
                        break;
                }
                    
            }
        }
        
        $rel->getSelect()->columns($ownerCols);
        return $rel;
    }
    
    protected function buildColumnsArgs($className, $assocName = null, $assocType = null, $foreignKey = null)
    {
        $attrs = Attributes::getAttributesFor($className);
        $tableName = $className::tableName();
        $columns = [];
        
        $count = count($this->includesData);
        $i = 0;
        $includeData = [
            'className' => $className,
            'rows' => []
        ];
        
        if ($assocName && $assocType && $foreignKey) {
            $includeData['assocName'] = $assocName;
            $includeData['assocType'] = $assocType;
            $includeData['foreignKey'] = $foreignKey;
        }
        
        foreach ($attrs->attributes() as $attr) {
            $attrAlias = 't' . $count . '_r' . $i;
            $columns[$attrAlias] = $attr->name();
            $i++;
            $includeData['rows'][$attrAlias] = $attr->name();
        }
        
        $this->includesData[] = $includeData;
        return $columns;
    }
    
    /**
     * Get an initialized model.
     * Initializes a model with 'where' conditions as attributes. If $attributes are
     * passed, they may be overwritten by conditions.
     *
     * @param array $attributes additional attributes to initialize the model with.
     * @return object
     * @see setWherePredicatesAsAttributes()
     */
    public function initialize(array $attributes = [])
    {
        $modelClass = $this->modelClass;
        $model      = new $modelClass($attributes);
        $this->setWherePredicatesAsAttributes($model);
        return $model;
    }
    
    /*
     * Find first or initialize model.
     *
     * @param array $attributes additional attributes to initialize the model with.
     * @return object
     */
    public function firstOrInitialize(array $attributes = [])
    {
        $model = $this->first();
        if (!$model) {
            $model = $this->initialize($attributes);
        }
        return $model;
    }
    
    /**
     * Create record.
     * Creates a record with 'where' conditions as attributes and returns the model.
     *
     * @param array $attributes additional attributes to create the record with.
     * @return object
     */
    public function create(array $attributes = [])
    {
        $model = $this->initialize($attributes);
        $model->save();
        return $model;
    }
    
    /**
     * Find first or create record.
     *
     * @param array $attributes additional attributes to create the record with.
     * @return object
     */
    public function firstOrCreate(array $attributes = [])
    {
        $model = $this->first();
        if (!$model) {
            $model = $this->create($attributes);
        }
        return $model;
    }
    
    /**
     * Paginate models.
     * Minimum $page must be 1, not 0.
     *
     * @var int|string $page
     * @var int|string $perPage
     */
    public function paginate($page, $perPage = null)
    {
        if ($page < 1) {
            $page = 1;
        }
        
        $modelClass = $this->modelClass;
        $paginator  = new Paginator($this->select, $modelClass::adapter());
        $items      = $paginator->getItems(($page - 1) * $perPage, $perPage);
        $collection = $this->buildCollection($items->toArray());
        
        $collection->setPage($page);
        $collection->setPerPage($perPage);
        $collection->setPaginator($paginator);
        
        return $collection;
    }
    
    public function load()
    {
        if (!$this->loaded) {
            $this->records = $this->buildCollection(
                $this->loadRecords($this->select)
            );
            $this->loaded  = true;
        }
        return $this;
    }
    
    public function reload()
    {
        $this->loaded = false;
        $this->load();
    }
    
    protected function loadRecords($select)
    {
        return parent::loadRecords(
            $this->selectDeletedRecords($select)
        );
    }
    
    /**
     * Modifies the query according to $deleted.
     *
     * @param Select $select
     * @return Select
     * @see $deleted
     */
    protected function selectDeletedRecords($select)
    {
        $modelClass = $this->modelClass;
        if ($modelClass::isRecoverable()) {
            if (!$this->deleted) {
                $select = clone $select;
                $select->where([
                    $modelClass::deletedAtAttribute() => $modelClass::deletedAtEmptyValue()
                ]);
            } elseif ($this->deleted === 'only') {
                $select = clone $select;
                $select->where([
                    $modelClass::deletedAtAttribute() => $modelClass::deletedAtValue()
                ]);
            }
        }
        return $select;
    }
    
    protected function buildSingleModel($dataRows)
    {
        $modelClass = $this->modelClass;
        if ($this->includesData) {
            $ownerData = array_shift($this->includesData);
            $ownerIdAlias = array_search($modelClass::primaryKey(), $ownerData['rows']);
            $firsRow = reset($dataRows);
            
            foreach ($ownerData['rows'] as $alias => $attrName) {
                $ownerAttrs[$attrName] = $firsRow[$alias];
            }
            
            $model = new $modelClass($ownerAttrs, false);
            
            foreach ($dataRows as $dataRow) {
                $this->processRow($dataRow, $model);
            }
        } else {
            $dataRow = array_shift($dataRows);
            $model = new $modelClass($dataRow, false);
        }
        return $model;
    }
    
    protected function buildCollection($rows)
    {
        $members    = [];
        $modelClass = $this->modelClass;
        
        if ($this->includesData) {
            $owners         = [];
            $ownerData      = array_shift($this->includesData);
            $ownerIdAlias   = array_search($modelClass::primaryKey(), $ownerData['rows']);
            
            foreach ($rows as $row) {
                if (!isset($owners[$row[$ownerIdAlias]])) {
                    foreach ($ownerData['rows'] as $alias => $attrName) {
                        $parentAttrs[$attrName] = $row[$alias];
                    }
                    $owners[$row[$ownerIdAlias]] = new $modelClass($parentAttrs, false);
                }
                $owner = $owners[$row[$ownerIdAlias]];
                
                $this->processRow($row, $owner);
            }
            
            $members = array_values($owners);
        } else {
            foreach ($rows as $row) {
                $members[] = new $modelClass($row, false);
            }
        }
        
        $this->includesData = [];
        return $modelClass::collection($members);
    }
    
    protected function processRow(array $row, $owner)
    {
        foreach ($this->includesData as $include) {
            $idAlias = array_search($include['className']::primaryKey(), $include['rows']);
            
            if (!$row[$idAlias]) {
                continue;
            }
            
            $attributes = [];
            foreach ($include['rows'] as $alias => $attrName) {
                $attributes[$attrName] = $row[$alias];
            }
            
            $includeClassName = $include['className'];
            
            switch ($include['assocType']) {
                case 'hasMany':
                    if (!$owner->getAssociation($include['assocName'], false)) {
                        $proxy = new CollectionProxy(
                            $include['className'],
                            $include['assocType'],
                            $owner,
                            $include['foreignKey']
                        );
                        $proxy->where([$include['foreignKey'] => $owner->id()]);
                        $proxy->loaded = true;
                        $proxy->records = $include['className']::collection();
                        $owner->setAssociation(
                            $include['assocName'],
                            $proxy,
                            true
                        );
                    }
                    
                    $owner->getAssociation($include['assocName'])->records[] = new $include['className']($attributes, false);
                    break;
                
                case 'belongsTo':
                    if (!$owner->getAssociation($include['assocName'], false)) {
                        $attrs = [];
                        
                        foreach ($include['rows'] as $alias => $attrName) {
                            $attrs[$attrName] = $row[$alias];
                        }
                        
                        $assoc = new $include['className']($attrs, false);
                        $owner->setAssociation(
                            $include['assocName'],
                            $assoc,
                            true
                        );
                    }
                    break;
            }
        }
    }
    
    /**
     * Sets 'where' conditions as a model's attributes.
     * For this to work, all 'where' parameters must be equal-operators, i.e. they 
     * must have been passed like this:
     * Model::where(["foo" => $foo, "bar" => $bar])->where(["baz" => $baz])->etc();
     *
     * @return void
     * @throws Exception\InvalidArgumentException if 'where' parameters aren't equal-operators.
     */
    protected function setWherePredicatesAsAttributes($model)
    {
        $attributes = $model->getAttributes();
        $where      = $this->getSelect()->where;
        
        foreach ($where->getPredicates() as $predicateData) {
            $predicate = $predicateData[1];
            if (
                !$predicate instanceof ZfPredicate\Operator ||
                $predicate->getOperator() != ZfPredicate\Operator::OP_EQ
            ) {
                throw new Exception\InvalidArgumentException(
                    "Invalid 'where' parameters passed for firstOrInitialize()"
                );
            }
            $attributes->set($predicate->getLeft(), $predicate->getRight());
        }
    }
    
    protected function adapter()
    {
        $modelClass = $this->modelClass;
        return $modelClass::adapter();
    }
}
