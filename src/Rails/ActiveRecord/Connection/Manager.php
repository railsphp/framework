<?php
namespace Rails\ActiveRecord\Connection;

use Zend\Db\Adapter\Adapter;
use Rails\ActiveRecord\Exception;
use Rails\ActiveRecord\Adapter\DriverCreator;

class Manager extends AbstractManager
{
    /**
     * Get the adapter for a specific connection. If the adapter doesn't
     * exist, it is created. If the connection config isn't found, an
     * exception is thrown.
     *
     * @return Adapter
     * @throw Exception\RuntimeException
     */
    public function getAdapter($connectionName, array $options = [])
    {
        if (!isset($this->adapters[$connectionName])) {
            if (!$this->connectionExists($connectionName)) {
                throw new Exception\RuntimeException(
                    sprintf('Connection doesn\'t exist: %s', $connectionName)
                );
            }
            
            $connectionConfig = $this->connections[$connectionName];
            $driver = DriverCreator::create($connectionName, $connectionConfig);
            
            $adapterParams = [
                'driver' => $driver
            ];
            
            if (isset($options['allowProfiler']) && !$options['allowProfiler']) {
                $adapterParams['profiler'] = false;
            }
            
            $adapter = new Adapter($adapterParams);
            $this->adapters[$connectionName] = $adapter;
            $driver->getConnection()->setAdapter($adapter);
            
            if (\Rails::cli()) {
                $adapter->setProfiler(new \Zend\Db\Adapter\Profiler\Profiler());
            }
        }
        return $this->adapters[$connectionName];
    }
    
    /**
     * Get the adapter for the default connection.
     *
     * @return Adapter
     */
    public function defaultAdapter()
    {
        return $this->getAdapter($this->defaultConnection);
    }
}
