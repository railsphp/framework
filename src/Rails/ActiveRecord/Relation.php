<?php
namespace Rails\ActiveRecord;

use Zend\Db\Sql\Select;
// use Rails\ActiveRecord\Relation\Select;
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
    
    /**
     * @var Relation[]
     */
    protected $includes = [];
    
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
    
    public function __clone()
    {
        parent::__clone();
        
        $includes       = $this->includes;
        $this->includes = [];
        foreach ($includes as $name => $include) {
            $this->includes[$name] = clone $include;
        }
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
        $rows = parent::first($limit);
        
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
     * $rel->includes('posts');
     * $rel->includes('posts', function($posts) { $posts->limit(5); });
     * $rel->includes(['posts', 'comments']);
     * $rel->includes([
     *     'posts'    => function($posts) { $posts->limit(5); },
     *     'comments' => function($cs) { $cs->includes('user'); }
     * ]);
     */
    public function includes($assocNames, \Closure $modifier = null)
    {
        $rel = $this->currentOrClone();
        
        $modelClass = $rel->modelClass;
        $assocs = Associations::forClass($rel->modelClass);
        
        if ($assocNames && $modifier) {
            $assocNames = [$assocNames => $modifier];
        }
        
        if (!is_array($assocNames)) {
            $assocNames = [ $assocNames ];
        }
        
        foreach ($assocNames as $assocName => $modifier) {
            if (is_int($assocName)) {
                $assocName = $modifier;
                $modifier  = null;
            }
            
            if (!isset($this->includes[$assocName])) {
                $options = $assocs->get($assocName);
                
                if ($options === false) {
                    throw new \Exception(sprintf(
                        "Association '%s' for class %s doesn't exist",
                        $assocName,
                        $modelClass
                    ));
                }
                
                $relation = isset($options['className']) ?
                    new self($options['className']) :
                    null;
                
                $this->includes[$assocName] = [
                    'options'  => $options,
                    'modifier' => $modifier,
                    'relation' => $relation
                ];
            }
                
            if ($modifier && $relation) {
                $modifier($this->includes[$assocName]['relation']);
            }
        }
        
        return $rel;
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
        
        if ($perPage === null) {
            if (!$perPage = $this->select->getRawState('limit')) {
                throw new Exception\InvalidArgumentException(
                    "No per page passed and no limit is set"
                );
            }
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
    
    public function order($order, $direction = null)
    {
        if ($direction) {
            if ($direction === 1) {
                $order .= ' ASC';
            } elseif ($direction === -1) {
                $order .= ' DESC';
            } else {
                throw new Exception\InvalidArgumentException(sprintf(
                    "Direction must be integer, either 1 or -1, %s passed",
                    $direction
                ));
            }
        }
        $rel = $this->currentOrClone();
        $rel->select->order($order);
        return $rel;
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
    
    protected function buildSingleModel($dataRow)
    {
        $modelClass = $this->modelClass;
        $model      = new $modelClass($dataRow, false);
        
        if ($this->includes) {
            $members = [$model->id() => $model];
            $rowsPks = [$model->id()];
            foreach ($this->includes as $assocName => $data) {
                $this->buildInclude($assocName, $data, $members, $rowsPks);
            }
        }
        return $model;
    }
    
    protected function buildCollection($rows)
    {
        $members    = [];
        $modelClass = $this->modelClass;
        $ownerPk    = $modelClass::primaryKey();
        $rowsPks    = array_map(function($row) use ($ownerPk) { return $row[$ownerPk]; }, $rows);
        
        foreach ($rows as $row) {
            $members[$row[$ownerPk]] = new $modelClass($row, false);
        }
        
        if ($this->includes) {
            foreach ($this->includes as $assocName => $data) {
                $this->buildInclude($assocName, $data, $members, $rowsPks);
            }
            
            $this->includes = [];
        }
        
        return $modelClass::collection(array_values($members));
    }
    
    # TODO: change method name.
    protected function buildInclude($assocName, array $data, array $members, $rowsPks)
    {
        $modelClass = $this->modelClass;
        $includeRel = $data['relation'];
        $options    = $data['options'];
        
        switch ($options['type']) {
            case 'hasMany':
                foreach (
                    $includeRel->where([$options['foreignKey'] => $rowsPks])
                    as $model
                ) {
                    $owner = $members[$model->getAttribute($options['foreignKey'])];
                    
                    if (!$owner->getAssociation($assocName, false)) {
                        $proxy = new CollectionProxy(
                            $options['className'],
                            $options['type'],
                            $owner,
                            $options['foreignKey']
                        );
                        $proxy->where([$options['foreignKey'] => $owner->id()]);
                        $proxy->loaded = true;
                        $proxy->records = $options['className']::collection();
                        $owner->setAssociation(
                            $assocName,
                            $proxy,
                            true
                        );
                    }
                    
                    $owner->getAssociation($assocName)->records[] = $model;
                }
                break;
            
            case 'hasOne':
                
                // $includeRel = new self($options['className']);
                $includeRel->where([$options['foreignKey'] => $rowsPks]);
                
                foreach ($members as $member) {
                    $included = $includeRel->search($options['foreignKey'], $member->id());
                    $member->setAssociation(
                        $assocName,
                        $included,
                        true
                    );
                }
                break;
            
            case 'belongsTo':
                if (isset($options['polymorphic'])) {
                    $polymorphicModels = [];
                    $assocTypeAttr = $assocName . 'Type';
                    $assocIdAttr   = $assocName . 'Id';
                    
                    foreach ($members as $member) {                        
                        $assocType = $member->$assocTypeAttr();
                        if (!isset($polymorphicModels[$assocType])) {
                            $polymorphicModels[$assocType] = [];
                        }
                        $polymorphicModels[$assocType][] = $member->$assocIdAttr();
                    }
                    
                    foreach (array_keys($polymorphicModels) as $modelName) {
                        $includeRel = new self($modelName);
                        if ($data['modifier']) {
                            $data['modifier']($includeRel);
                        }
                        
                        $models = $includeRel->where([$modelName::primaryKey() => $polymorphicModels[$modelName]]);
                        
                        foreach ($models as $model) {
                            foreach ($members as $member) {
                                if ($member->$assocIdAttr() == $model->id()) {
                                    $member->setAssociation(
                                        $assocName,
                                        $model,
                                        true
                                    );
                                    break;
                                }
                            }
                        }
                    }
                    break;
                }
                
                $assocPk = $options['className']::primaryKey();
                $includeRel
                    ->where([
                        $assocPk => array_unique(
                            array_map(
                                function($x) use ($options) { return $x->getAttribute($options['foreignKey']); },
                                $members
                            )
                        )
                    ]);
                
                foreach ($members as $member) {
                    $included = $includeRel->search($assocPk, $member->getAttribute($options['foreignKey']));
                    $member->setAssociation(
                        $assocName,
                        $included,
                        true
                    );
                }
                break;
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
