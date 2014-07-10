<?php
namespace Rails\ActionView;

use Rails\ActiveModel\Base as ActiveModel;

abstract class Presenter extends Helper
{
    protected $object;
    
    public function __call($methodName, $args)
    {
        if ($helper = $this->helperSet->findHelper($methodName)) {
            return call_user_func_array([$helper, $methodName], $args);
        }
        return $this->presentDefault($methodName);
    }
    
    public function presentDefault($propertyName)
    {
        if (!$this->object) {
            throw new Exception\BadMethodCallException(
                "There's no object set"
            );
        } elseif (!$this->object instanceof ActiveModel) {
            throw new Exception\BadMethodCallException(
                "Class of object must be child of ActiveModel\\Base"
            );
        }
        
        return $this->defaultPresentation($this->object->getProperty($propertyName));
    }
    
    public function setObject($object)
    {
        $this->object = $object;
        return $this;
    }
    
    public function object()
    {
        return $this->object;
    }
    
    protected function defaultPresentation($value)
    {
        return $this->h((string)$value);
    }
}
