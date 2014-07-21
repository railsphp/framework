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
}
