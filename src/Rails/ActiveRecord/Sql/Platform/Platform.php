<?php
namespace Rails\ActiveRecord\Sql\Platform;

use Zend\Db\Sql\Platform\Platform as BasePlatform;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Platform as ZfPlatform;
use Rails\ActiveRecord\Relation\SelectDecorator;

/**
 * Use custom SelectDecorators.
 */
class Platform extends BasePlatform
{
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        $platform = $adapter->getPlatform();
        switch (strtolower($platform->getName())) {
            case 'mysql':
                $platform = new ZfPlatform\Mysql\Mysql();
                $this->decorators = $platform->decorators;
                $this->setTypeDecorator('Zend\Db\Sql\Select', new Mysql\SelectDecorator());
                break;
            case 'sqlserver':
                $platform = new ZfPlatform\SqlServer\SqlServer();
                $this->decorators = $platform->decorators;
                $this->setTypeDecorator('Zend\Db\Sql\Select', new SqlServer\SelectDecorator());
                break;
            case 'oracle':
                $platform = new ZfPlatform\Oracle\Oracle();
                $this->decorators = $platform->decorators;
                $this->setTypeDecorator('Zend\Db\Sql\Select', new Oracle\SelectDecorator());
                break;
            default:
        }
    }
}
