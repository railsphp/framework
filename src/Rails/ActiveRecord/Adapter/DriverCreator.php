<?php
namespace Rails\ActiveRecord\Adapter;

use Zend\Db\Adapter\Driver as ZfDriver;

class DriverCreator
{
    /**
     * Creates a connection out of $parameters which is set
     * to the corresponding driver and returns the driver.
     *
     * $parameters would be connection parameters extracted from the
     * config/database.yml file.
     *
     * This is the Zend\Db\Adapter\Adapter::createDriver method, except that
     * the "driver" key must be a string, it can't be actual driver, and as noted
     * above, the connection is created here instead of letting the driver
     * create it itself.
     */
    public static function create($connectionName, array $parameters)
    {
        if (!isset($parameters['driver'])) {
            throw new Exception\InvalidArgumentException(__FUNCTION__ . ' expects a "driver" key to be present inside the parameters');
        }
        if (!is_string($parameters['driver'])) {
            throw new Exception\InvalidArgumentException(__FUNCTION__ . ' expects "driver" to be a string');
        }

        $options = array();
        if (isset($parameters['options'])) {
            $options = (array) $parameters['options'];
            unset($parameters['options']);
        }

        $driverName = strtolower($parameters['driver']);
        switch ($driverName) {
            case 'mysqli':
                $conn   = new Driver\Mysqli\Connection($parameters);
                $driver = new ZfDriver\Mysqli\Mysqli($conn, null, null, $options);
                break;
            case 'sqlsrv':
                $conn   = new Driver\Sqlsrv\Connection($parameters);
                $driver = new ZfDriver\Sqlsrv\Sqlsrv($conn);
                break;
            case 'oci8':
                $conn   = new Driver\Oci8\Connection($parameters);
                $driver = new ZfDriver\Oci8\Oci8($conn);
                break;
            case 'pgsql':
                $conn   = new Driver\Pgsql\Connection($parameters);
                $driver = new ZfDriver\Pgsql\Pgsql($conn);
                break;
            case 'ibmdb2':
                $conn   = new Driver\IbmDb2\Connection($parameters);
                $driver = new ZfDriver\IbmDb2\IbmDb2($conn);
                break;
            case 'pdo':
            default:
                if ($driverName == 'pdo' || strpos($driverName, 'pdo') === 0) {
                    $conn   = new Driver\Pdo\Connection($parameters);
                    $driver = new ZfDriver\Pdo\Pdo($conn);
                }
                break;
        }

        if (!isset($driver) || !$driver instanceof ZfDriver\DriverInterface) {
            throw new Exception\InvalidArgumentException('ZfDriver\DriverInterface expected');
        }

        $conn->setName($connectionName);
        
        return $driver;
    }
}
