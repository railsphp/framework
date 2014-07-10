<?php
namespace Rails\ActiveRecord\Persistence\PersistedModel\Methods;

use Rails\ActiveRecord\Connection\AbstractManager;

/**
 * The class using this trait must define a $connectionManager static property.
 */
trait ConnectionMethodsTrait
{
    /**
     * Sets connection manager.
     */
    public static function setConnectionManager(AbstractManager $connectionManager)
    {
        static::$connectionManager = $connectionManager;
    }
    
    /**
     * Gets connection manager.
     *
     * @return ConnectionManager
     */
    public static function connectionManager()
    {
        if (!static::$connectionManager) {
            if (self::services()->has('defaultConnectionManager')) {
                static::$connectionManager = self::getService('defaultConnectionManager');
            }
        }
        return static::$connectionManager;
    }
    
    /**
     * Get the connection name for models. Can be overwritten in children classes
     * to set a different connection name.
     */
    public static function connectionName()
    {
        return self::connectionManager()->defaultConnection();
    }
    
    /**
     * Get the database adapter's connection.
     * Note that the actual class returned here is one of the
     * \Rails\ActiveRecord\Adapter\Driver\*\Connection.
     *
     * @return \Zend\Db\Adapter\ConnectionInterface
     */
    public static function connection()
    {
        return static::adapter()->getDriver()->getConnection();
    }
}
