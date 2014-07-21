<?php
namespace Rails\ActionDispatch\Test\Http;

use Rails\ActionDispatch\Http\Request as Base;

class Request extends Base
{
    protected $serverVars;
    
    public function setServerVars($serverVars)
    {
        $this->serverVars = $serverVars;
    }
    
    public function getParam($name)
    {
        $name = strtoupper($name);
        if (isset($this->serverVars[$name])) {
            return $this->serverVars[$name];
        }
        return null;
    }
}
