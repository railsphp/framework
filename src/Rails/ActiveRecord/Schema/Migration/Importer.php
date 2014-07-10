<?php
namespace Rails\ActiveRecord\Schema\Migration;

use Rails\ActiveRecord\Adapter\Schema;
use Zend\Db\Adapter\Driver\ConnectionInterface;

class Importer
{
    public function import(ConnectionInterface $connection, $filepath, Schema\AbstractImporter $importer = null)
    {
        if (!$importer) {
            switch ($connection->getDriverName()) {
                case 'Mysql':
                    $importer = new Schema\MySql\Importer($connection);
                    break;
                
                case 'Sqlite':
                    $importer = new Schema\Sqlite\Importer($connection);
                    break;
                
                default:
                    throw new Exception\RuntimeException(sprintf(
                        "Unknown/unsupported driver %s",
                        $driverName
                    ));
            }
        }
        
        $importer->importFile($filepath);
    }
}
