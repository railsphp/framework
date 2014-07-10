<?php
namespace Rails\ActiveRecord\Adapter\Schema;

use Zend\Db\Adapter\Driver\ConnectionInterface;

abstract class AbstractExporter
{
    protected $connection;
    
    /**
     * @param string $schemaName
     */
    abstract public function export($schemaName = null);
    
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }
}
