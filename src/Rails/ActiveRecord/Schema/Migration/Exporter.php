<?php
namespace Rails\ActiveRecord\Schema\Migration;

use Rails\ActiveRecord\Adapter\Schema;
use Zend\Db\Adapter\Driver\ConnectionInterface;

class Exporter
{
    public function export(ConnectionInterface $connection, Schema\AbstractExpoter $exporter = null, $schemaName = null)
    {
        if (!$exporter) {
            switch ($connection->getDriverName()) {
                case 'Mysql':
                    $exporter = new Schema\MySql\Exporter($connection);
                    break;
                
                case 'Sqlite':
                    $exporter = new Schema\Sqlite\Exporter($connection);
                    break;
                
                default:
                    throw new Exception\RuntimeException(sprintf(
                        "Unknown/unsupported driver %s",
                        $driverName
                    ));
            }
        }
        
        return $exporter->export($schemaName);
    }
}
