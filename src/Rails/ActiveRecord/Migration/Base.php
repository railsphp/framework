<?php
namespace Rails\ActiveRecord\Migration;

use Zend\Db\Adapter\Adapter;

class Base
{
    protected $adapter;
    
    protected $schema;
    
    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->setSchema();
        // $this->connection = \Rails\ActiveRecord\ActiveRecord::connection();
    }
    
    public function __call($method, $params)
    {
        call_user_func_array([$this->schema, $method], $params);
    }
    
    protected function setSchema()
    {
        $this->schema = new \Rails\ActiveRecord\Schema\Schema($this->adapter);
    }
}
