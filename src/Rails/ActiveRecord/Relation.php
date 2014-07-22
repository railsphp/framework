<?php
namespace Rails\ActiveRecord;

use Zend\Db\Sql\Select;
use Zend\Db\Sql\Predicate as ZfPredicate;
use Zend\Paginator\Adapter\DbSelect as Paginator;

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
    
    public function first($limit = 1)
    {
        $data = parent::first($limit);
        
        if ($data) {
            if ($limit == 1) {
                $modelClass = $this->modelClass;
                $model = new $modelClass($data, false);
                return $model;
            } else {
                return $this->buildCollection($data);
            }
        }
        
        return null;
    }
    
    public function take($limit = 1)
    {
        $data = parent::take($limit);
        
        if ($data) {
            if ($limit == 1) {
                $modelClass = $this->modelClass;
                $model = new $modelClass($data, false);
                return $model;
            } else {
                return $this->buildCollection($data);
            }
        }
        
        return null;
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
    
    /**
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
        
        $members = [];
        foreach ($items->toArray() as $attributes) {
            $members[] = new $modelClass($attributes, false);
        }
        
        $collection = new Collection($members);
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
    
    protected function buildCollection($rows)
    {
        $members    = [];
        $modelClass = $this->modelClass;
        foreach ($rows as $row) {
            $members[] = new $modelClass($row, false);
        }
        return $modelClass::collection($members);
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
