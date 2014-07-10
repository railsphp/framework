<?php
namespace Rails\ActiveRecord\Adapter\Driver\IbmDb2;

use Zend\Db\Adapter\Driver\IbmDb2\Connection as Base;
use Rails\ActiveRecord\Adapter\Driver\ConnectionModifierTrait;

class Connection extends Base
{
    use ConnectionModifierTrait;
    
    public function getDriverName()
    {
        return 'IbmDb2';
    }
}
