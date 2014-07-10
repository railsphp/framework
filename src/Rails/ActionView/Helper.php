<?php
namespace Rails\ActionView;

use Rails\ServiceManager\ServiceLocatorAwareTrait;

abstract class Helper
{
    use ServiceLocatorAwareTrait;
    
    /**
     * @var HelperSet
     */
    protected $helperSet;
    
    public function __construct(Helper\HelperSet $helperSet)
    {
        $this->helperSet = $helperSet;
    }
    
    public function __call($methodName, $arguments)
    {
        return $this->helperSet->invoke($methodName, $arguments);
    }
    
    public function request()
    {
        return $this->helperSet->request();
    }
    
    public function params()
    {
        return $this->helperSet->params();
    }
    
    public function helperSet()
    {
        return $this->helperSet;
    }
    
    public function base()
    {
        return $this->helperSet->baseHelper();
    }
}
