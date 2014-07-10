<?php
namespace Rails\ActiveRecord\Adapter\Driver\Mysqli;

use Zend\Db\Adapter\Driver\Mysqli\Connection as Base;
use Rails\ActiveRecord\Adapter\Driver\ConnectionModifierTrait;

class Connection extends Base
{
    use ConnectionModifierTrait;
    
    public function getDriverName()
    {
        return 'Mysqli';
    }
}
