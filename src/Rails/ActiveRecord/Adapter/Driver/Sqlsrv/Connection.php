<?php
namespace Rails\ActiveRecord\Adapter\Driver\Sqlsrv;

use Zend\Db\Adapter\Driver\Sqlsrv\Connection as Base;
use Rails\ActiveRecord\Adapter\Driver\ConnectionModifierTrait;

class Connection extends Base
{
    use ConnectionModifierTrait;
    
    public function getDriverName()
    {
        return 'Sqlsrv';
    }
}
