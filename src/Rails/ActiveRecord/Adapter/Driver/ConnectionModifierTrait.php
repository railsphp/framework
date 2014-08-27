<?php
namespace Rails\ActiveRecord\Adapter\Driver;

use Zend\Db\Sql\Sql;
use Rails\ActiveRecord\Sql\Platform\Platform;

/**
 * Add a little more methods to retrieve data to the Connections.
 */
trait ConnectionModifierTrait
{
    protected $adapter;
    
    /**
     * Connection name
     *
     * @var string
     */
    protected $name;
    
    protected $sql;
    
    public function setAdapter($adapter)
    {
        if (!$this->adapter) {
            $this->adapter = $adapter;
        }
    }
    
    public function setName($name)
    {
        $this->name = $name;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * This method is overwritten by connections.
     *
     * @return string
     */
    public function getDriverName()
    {
    }
    
    /**
     * Returns array of arrays with column names as keys and column
     * values as values; i.e., returns array of rows.
     * E.g. "SELECT * FROM users"
     *  -> [ [ 'id' => '1', 'name' => 'Foo' ], ... ]
     *
     * @return null|array
     */
    public function selectAll($sql)
    {
        $result = $this->execute($sql);
        $all    = [];
        foreach ($result as $row) {
            $all[] = $row;
        }
        return $all;
    }
    
    /**
     * Returns one array with column names as keys and column
     * values as values; i.e., returns the first row.
     * E.g. "SELECT * FROM users"
     *  -> ['id' => '1', 'name' => 'Foo'];
     *
     * @return null|array
     */
    public function selectOne($sql)
    {
        $result = $this->execute($sql);
        if ($result->count()) {
            return $result->current();
        }
    }
    
    /**
     * Returns a single value; i.e., the first column value of the
     * first row.
     * E.g. "SELECT id FROM users WHERE name = 'foo'"
     *  -> '1'
     *
     * @return null|string
     */
    public function selectValue($sql)
    {
        $result = $this->execute($sql);
        if ($result->count()) {
            $row = $result->current();
            return reset($row);
        }
    }
    
    /**
     * Returns an array of the values of the first column in
     * the select statement; i.e., the value of the first column
     * of all the fetched rows.
     * E.g. "SELECT id FROM users"
     *  -> ['1', '2', ...]
     *
     * @return null|array
     */
    public function selectValues($sql)
    {
        $result = $this->execute($sql);
        if ($result->count()) {
            $values = [];
            foreach ($result as $row) {
                $values[] = reset($row);
            }
            return $values;
        }
    }
    
    public function getSql()
    {
        if (!$this->sql) {
            $this->sql = new Sql($this->adapter, null, new Platform($this->adapter));
        }
        return $this->sql;
    }
    
    public function table($tableName)
    {
        return new \Rails\ActiveRecord\Relation\SqlRelation($this->getSql(), $tableName);
    }
}
