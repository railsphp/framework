<?php
namespace Rails\ActiveRecord\Metadata;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Metadata\Metadata as Zf2Metadata;

/**
 * This class modifies its parent so it will use
 * Rails' Source classes.
 */
class Metadata extends Zf2Metadata
{
    protected function createSourceFromAdapter(Adapter $adapter)
    {
        switch ($adapter->getPlatform()->getName()) {
            case 'MySQL':
                return new Source\MysqlMetadata($adapter);
            case 'SQLServer':
                return new Source\SqlServerMetadata($adapter);
            case 'SQLite':
                return new Source\SqliteMetadata($adapter);
            case 'PostgreSQL':
                return new Source\PostgresqlMetadata($adapter);
            case 'Oracle':
                return new Source\OracleMetadata($adapter);
        }

        throw new \Exception('cannot create source from adapter');
    }
}
