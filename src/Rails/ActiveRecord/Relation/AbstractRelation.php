<?php
namespace Rails\ActiveRecord\Relation;

use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Paginator\Adapter\DbSelect as Paginator;
use Zend\Db\Adapter\Exception\InvalidQueryException;

abstract class AbstractRelation implements \IteratorAggregate, \Countable
{
    /**
     * @var Select
     */
    protected $select;
    
    /**
     * @var Relation\Predicate
     */
    protected $where;
    
    /**
     * @var Relation\Predicate
     */
    protected $having;
    
    protected $records;
    
    protected $loaded = false;
    
    /**
     * @return \Zend\Db\Adapter\AdapterInterface
     */
    abstract protected function adapter();
    
    public function __construct()
    {
        $this->select = new Select();
    }
    
    public function __clone()
    {
        $this->select  = clone $this->select;
        $this->loaded  = false;
        $this->records = null;
    }
    
    public function getIterator()
    {
        $this->load();
        return new \ArrayObject($this->records);
    }
    
    public function loaded()
    {
        return $this->loaded;
    }
    
    /**
     * Returns the actual Select object. This method has nothing to do
     * with select() or the query itself.
     */
    public function getSelect()
    {
        return $this->select;
    }
    
    /**
     * Pass strings of column names:
     * $rel->select('id', 'name');
     * $rel->select('u.id');
     *
     * Or pass an array:
     * $rel->select(['id', 'name']);
     *
     * Or pass an array of "alias => field" key/values:
     * $rel->select(['user_id' => 'u.id', 'post_title' => 'p.id']);
     */
    public function select($params/*, ...*/)
    {
        $rel = $this->currentOrClone();
        
        $params = func_get_args();
        if (is_array($params[0])) {
            $params = array_shift($params);
        }
        $rel->select->columns($params);
        return $rel;
    }
    
    /**
     * Plain table name without alias:
     * $rel->from('users');
     *
     * These will set an alias:
     * $rel->from('users, 'u');
     * $rel->from(['u' => 'users']);
     */
    public function from($tableName, $alias = null)
    {
        $rel = $this->currentOrClone();
        
        if ($alias) {
            $rel->select->from([$alias => $tableName]);
        } else {
            $rel->select->from($tableName);
        }
        return $rel;
    }
    
    public function join($tableName, $on, $columns = false, $type = Select::JOIN_INNER)
    {
        $rel = $this->currentOrClone();
        $rel->select->join($tableName, $on, $columns, $type);
        return $rel;
    }
    
    public function order($order)
    {
        $rel = $this->currentOrClone();
        $rel->select->order($order);
        return $rel;
    }
    
    /**
     * Passes arguments to $where and returns $where.
     */
    public function where()
    {
        $rel = $this->currentOrClone();
        
        if (!$rel->where) {
            $rel->where = new Where($rel);
        }
        
        if (func_num_args()) {
            call_user_func_array([$rel->where, 'condition'], func_get_args());
        }
        return $rel->where;
    }
    
    public function rawWhere($expression)
    {
        $rel = $this->currentOrClone();
        return $rel->where($this->raw($expression));
    }
    
    /**
     * Passes arguments to $having and returns $having.
     */
    public function having()
    {
        $rel = $this->currentOrClone();
        
        if (!$rel->having) {
            $rel->having = new Having($rel);
        }
        
        if (func_num_args()) {
            call_user_func_array([$rel->having, 'condition'], func_get_args());
        }
        return $rel->having;
    }
    
    public function first($limit = 1)
    {
        $select = clone $this->select;
        $this->orderByIdIfUnordered($select);
        $select->limit($limit);
        $rows = $this->loadRecords($select);
        
        if (count($rows)) {
            if ($limit == 1) {
                return current($rows);
            } else {
                return $rows;
            }
        }
        return null;
    }
    
    protected function orderByIdIfUnordered($select)
    {
        if (!$select->getRawState(Select::ORDER)) {
            $from = $select->getRawState(Select::TABLE);
            if ($from) {
                $order = $from . '.id';
            } else {
                $order = 'id';
            }
            $select->order($order);
        }
    }
    
    public function take($limit = 1)
    {
        $select = clone $this->select;
        $select->limit($limit);
        $rows = $this->loadRecords($select);
        
        if (count($rows)) {
            if ($limit == 1) {
                return current($rows);
            } else {
                return $rows;
            }
        }
        return null;
    }
    
    /**
     * Pass many string values or an array of strings.
     *
     * @param string|array $columnName
     */
    public function pluck($columnName/*...$columnNames*/)
    {
        if (is_array($columnName)) {
            $columnNames = $columnName;
        } else {
            $columnNames = func_get_args();
        }
        
        $select = clone $this->select;
        $select->columns($columnNames);
        $records = $this->loadRecords($select);
        
        if (count($columnNames) == 1) {
            return array_map(function($x) {
                return current($x);
            }, $records);
        } else {
            return $records;
        }
    }
    
    /**
     * Issues a count query and returns the result.
     *
     * @return int|false
     */
    public function count()
    {
        $select = clone $this->select;
        $select->reset(Select::COLUMNS);
        $select->columns([
            'c' => $this->raw('COUNT(*)')
        ]);
        
        $rows = $this->loadRecords($select);
        if (isset($rows[0]['c'])) {
            return (int)$rows[0]['c'];
        }
        
        return false;
    }
    
    public function limit($limit)
    {
        $rel = $this->currentOrClone();
        $rel->select->limit($limit);
        return $rel;
    }
    
    public function offset($offset)
    {
        $rel = $this->currentOrClone();
        $rel->select->offset($offset);
        return $rel;
    }
    
    /**
     * Paginate models.
     * Minimum $page must be 1, not 0.
     *
     * @var int|string $page
     * @var int|string $perPage
     * @return array
     */
    public function paginate($page, $perPage = null)
    {
        if ($page < 1) {
            $page = 1;
        }
        
        $paginator = new Paginator($this->select, $this->adapter());
        $items     = $paginator->getItems(($page - 1) * $perPage, $perPage);
        
        return $items->toArray();
    }
    
    public function toSql()
    {
        return $this->getSqlString($this->select);
    }
    
    /**
     * Merge relations conditions.
     * Returns a clone of $this with merged conditions from $other.
     *
     * @return Relation
     */
    public function merge(self $other)
    {
        $new      = clone $this;
        $newWhere = $new->select->where;
        
        foreach ($other->select->where->getPredicates() as $pData) {
            $newWhere->addPredicate($pData[1], $pData[0]);
        }
        
        return $new;
    }
    
    public function raw($expression)
    {
        return new Expression($expression);
    }
    
    public function records()
    {
        $this->load();
        return $this->records;
    }
    
    public function load()
    {
        if (!$this->loaded) {
            $this->records = $this->loadRecords($this->select);
            $this->loaded  = true;
        }
        return $this;
    }
    
    protected function getSqlString($select)
    {
        return $this->adapter()
            ->getDriver()
            ->getConnection()
            ->getSql()
            ->getSqlStringForSqlObject($select);
    }
    
    /**
     * @param Select $select
     * @return \Zend\Db\Adapter\Driver\ResultInterface
     */
    protected function loadRecords($select)
    {
        $sql     = $this->getSqlString($select);
        $adapter = $this->adapter();
        
        try {
            $records = $adapter->query($sql, $adapter::QUERY_MODE_EXECUTE)->toArray();
        } catch (InvalidQueryException $e) {
            $message = $e->getMessage() . "\nQuery:\n" . $sql;
            throw new InvalidQueryException($message, 0, $e);
        }
        
        return $records;
    }
    
    /**
     * Loading a relation will "freeze" it, which means that new conditions
     * can't be applied to it. Rather, when calling many of the methods
     * (like where() or limit()), a clone of the relation will be created,
     * setting the new conditions to it and returning it. This is done in order
     * to avoid losing/overwriting the already loaded records.
     *
     * <pre>
     * $posts = Post::where('id', '>', 5);
     * foreach ($posts as $post) {
     *    // Posts loaded
     * }
     *
     * // Setting a new condition will be done in the cloned relation,
     * // and the clone is returned.
     * $posts->where('user_id = ?', 5);
     *
     * // Since the clone wasn't captured above, the next foreach will have the
     * // same records as before.
     * foreach ($posts as $post) {
     *     // Same records
     * }
     *
     * // To avoid this, either capture the clone in a variable,
     * // or load the clone directly, like:
     * foreach ($posts->where('user_id = ?', 5) as $post) {
     *     // Filtered records
     * }
     * </pre>
     */
    protected function currentOrClone()
    {
        if ($this->loaded()) {
            return clone $this;
        }
        return $this;
    }
}
