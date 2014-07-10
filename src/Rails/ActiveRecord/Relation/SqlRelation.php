<?php
namespace Rails\ActiveRecord\Relation;

use Rails\ActiveRecord\Exception;
use Zend\Db\Sql as ZfSql;
use Zend\Db\Adapter\AdapterInterface;
// use Zend\Db\Adapter\Adapter;

class SqlRelation extends AbstractRelation
{
    /**
     * @var AdapterInterface
     */
    protected $adapter;
    
    protected $tableName;
    
    public function __construct(AdapterInterface $adapter, $tableName)
    {
        parent::__construct();
        
        $this->adapter   = $adapter;
        $this->tableName = $tableName;
        $this->from($this->tableName);
    }
    
    public function tableName()
    {
        return $this->tableName;
    }
    
    /**
     * Pass true as $name to return to last generated id.
     * Pass string and it will be passed to the id retriever method.
     * Pass false to skip retrieving the last generated id and return true.
     *
     * @throws Exception\RecordNotSavedException
     */
    public function insert(array $columnsValuesPairs, $name = true)
    {
        $adapter   = $this->adapter;
        $insert    = new ZfSql\Insert($this->tableName);
        $insert->values($columnsValuesPairs);
        $sqlString = $insert->getSqlString($adapter->getPlatform());
        
        try {
            $result = $adapter->query($sqlString, $adapter::QUERY_MODE_EXECUTE);
        } catch (AdapterException\ExceptionInterface $e) {
            throw new Exception\RecordNotSavedException($e->getMessage(), 0, $e);
        }
        
        if (true === $name) {
            $value = $adapter->getDriver()->getLastGeneratedValue();
            if ($value === '0') {
                return true;
            } else {
                return $value;
            }
        } elseif (is_string($name)) {
            return $adapter->getDriver()->getLastGeneratedValue($name);
        } else {
            return true;
        }
    }
    
    /**
     * @throws Exception\RecordNotSavedException
     */
    public function update(array $columnsValuesPairs)
    {
        $update = new ZfSql\Update($this->tableName);
        $update->set($columnsValuesPairs);
        $adapter = $this->adapter;
        
        if ($this->select->where) {
            $update->where($this->select->where);
        }
        
        $sqlString = $update->getSqlString($adapter->getPlatform());
        
        try {
            $result = $adapter->query($sqlString, $adapter::QUERY_MODE_EXECUTE);
        } catch (AdapterException\ExceptionInterface $e) {
            throw new Exception\RecordNotSavedException($e->getMessage(), 0, $e);
        }
        
        if (!$result->count()) {
            throw new Exception\RecordNotSavedException(
                "No rows were affected"
            );
        }
        
        return true;
    }
    
    public function delete()
    {
        $delete  = new ZfSql\Delete($this->tableName);
        $adapter = $this->adapter;
        
        if ($this->select->where) {
            $delete->where($this->select->where);
        }
        
        $sqlString = $delete->getSqlString($adapter->getPlatform());
        
        try {
            $result = $adapter->query($sqlString, $adapter::QUERY_MODE_EXECUTE);
        } catch (AdapterException\ExceptionInterface $e) {
            throw new Exception\RecordNotDestroyedException($e->getMessage(), 0, $e);
        }
        
        if (!$result->count()) {
            throw new Exception\RecordNotDestroyedException(
                "No rows were affected"
            );
        }
        
        return true;
    }
    
    protected function adapter()
    {
        return $this->adapter;
    }
}
