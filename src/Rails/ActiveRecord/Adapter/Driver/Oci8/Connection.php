<?php
namespace Rails\ActiveRecord\Adapter\Driver\Oci8;

use Zend\Db\Adapter\Driver\Oci8\Connection as Base;
use Rails\ActiveRecord\Adapter\Driver\ConnectionModifierTrait;

class Connection extends Base
{
    use ConnectionModifierTrait;
    
    public function getDriverName()
    {
        return 'Oci8';
    }
}
