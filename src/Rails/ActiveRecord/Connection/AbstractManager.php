<?php
namespace Rails\ActiveRecord\Connection;

class AbstractManager
{
    /**
     * This would be the database configuration found in
     * config/database.yml.
     *
     * @var array
     */
    protected $connections = [];
    
    /**
     * Holds the created adapters.
     *
     * @var array
     */
    protected $adapters = [];
    
    /**
     * Available options:
     *
     * allowProfiler => bool
     *  Set to false so no profiler will be created. This setting would
     *  be set to false in production environments.
     *
     * @var array
     */
    protected $options = [];
    
    /**
     * The default connection that will be used.
     *
     * @var string
     */
    protected $defaultConnection;
    
    /**
     * @param array $connections
     * @param string $defaultConnection
     * @param array $options
     */
    public function __construct(array $connections, $defaultConnection, array $options = [])
    {
        $this->defaultConnection = $defaultConnection;
        $this->connections = $connections;
        $this->options = $options;
    }
    
    public function connectionExists($connectionName)
    {
        return isset($this->connections[$connectionName]);
    }
    
    public function removeAdapter($connectionName)
    {
        unset($this->adapters[$connectionName]);
    }
    
    /**
     * Get the name of the default connection.
     *
     * @return string
     */
    public function defaultConnection()
    {
        return $this->defaultConnection;
    }
    
    /**
     * @param string $connectionName
     * @return bool     True if connection exists, false otherwise.
     */
    public function setDefaultConnection($connectionName)
    {
        if (isset($this->connections[$connectionName])) {
            $this->defaultConnection = $connectionName;
            return true;
        }
        return false;
    }
}
