<?php
namespace Rails\ActiveRecord\Adapter\Driver\Pgsql;

use Zend\Db\Adapter\Driver\Pgsql\Connection as Base;
use Rails\ActiveRecord\Adapter\Driver\ConnectionModifierTrait;

class Connection extends Base
{
    use ConnectionModifierTrait;
    
    public function getDriverName()
    {
        return 'Pgsql';
    }
}
