<?php
namespace Rails\ActionDispatch\Test\Http;

use Rails\ActionDispatch\Http\Parameters as Base;

class Parameters extends Base
{
    protected $getParams = [];
    
    protected $postParams = [];
    
    public function setGetParams(array $params)
    {
        $this->getParams = $params;
    }
    
    public function setPostParams(array $params)
    {
        $this->postParams = $params;
    }
    
    public function setDeleteParams(array $params)
    {
        $this->deleteParams = $params;
    }
    
    public function setPutParams(array $params)
    {
        $this->putParams = $params;
    }
    
    public function setPatchParams(array $params)
    {
        $this->patchParams = $params;
    }
    
    public function setOtherVerbParams(array $params)
    {
        $this->otherVerbParams = $params;
    }
    
    public function get()
    {
        return $this->getParams;
    }
    
    public function post()
    {
        return $this->postParams;
    }
    
    public function delete()
    {
        return $this->deleteParams;
    }
    
    public function put()
    {
        return $this->putParams;
    }
    
    public function patch()
    {
        return $this->patchParams;
    }
    
    public function getParam($prop)
    {
        if (isset($this->routeParams[$prop])) {
            return $this->routeParams[$prop];
        } elseif (isset($this->getParams[$prop])) {
            return $this->getParams[$prop];
        } elseif (isset($this->postParams[$prop])) {
            return $this->postParams[$prop];
        } elseif (isset($this->putParams[$prop])) {
            return $this->putParams[$prop];
        } elseif (isset($this->deleteParams[$prop])) {
            return $this->deleteParams[$prop];
        } elseif (isset($this->patchParams[$prop])) {
            return $this->patchParams[$prop];
        } elseif (isset($this->otherVerbParams[$prop])) {
            return $this->otherVerbParams[$prop];
        }
        
        return null;
    }
}
