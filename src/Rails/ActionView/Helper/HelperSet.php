<?php
namespace Rails\ActionView\Helper;

use Rails\ActionView\Exception;
use Rails\ActionView\Helper as AbstractHelper;
use Rails\Routing\Route\RouteSet;

class HelperSet
{
    protected static $defaultHelpers = [];
    
    protected $baseHelper;
    
    protected $helpers = [];
    
    /**
     * Cached methods.
     */
    protected $methods = [];
    
    protected $request;
    
    protected $params;
    
    protected $assigns = [];
    
    protected $session;
    
    public static function addDefaultHelper($className)
    {
        self::$defaultHelpers[] = $className;
    }
    
    public static function prependDefaultHelper($className)
    {
        array_unshift(self::$defaultHelpers, $className);
    }
    
    public function __construct($request = null, $params = null, $session = null, $assigns = null)
    {
        if ($request) {
            $this->request = $request;
        }
        if ($params) {
            $this->params = $params;
        }
        if ($session) {
            $this->session = $session;
        }
        if ($assigns) {
            $this->assigns = $assigns;
        }
        $this->setDefaultHelpers();
    }
    
    public function __call($method, $arguments)
    {
        return $this->invoke($method, $arguments);
    }
    
    public function request()
    {
        return $this->request;
    }
    
    public function params()
    {
        return $this->params;
    }
    
    public function assigns()
    {
        return $this->assigns;
    }
    
    public function setRouteSet(RouteSet $routeSet)
    {
        $this->baseHelper()->setRouteSet($routeSet);
    }
    
    public function setBaseHelper(BaseHelper $baseHelper)
    {
        $this->baseHelper = $baseHelper;
        return $this;
    }
    
    public function baseHelper()
    {
        if (!$this->baseHelper) {
            $this->baseHelper = new BaseHelper($this);
        }
        return $this->baseHelper;
    }
    
    public function addHelper($helper)
    {
        if (is_string($helper)) {
            $helper = new $helper($this);
        } elseif (!is_object($helper)) {
            throw new Exception\InvalidArgumentException(
                "Argument must be either string or instance of Rails\ActionView\Helper"
            );
        }
        
        if (!$helper instanceof AbstractHelper) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    "Object passed must be instance of Rails\ActionView\Helper, passed %s",
                    get_class($helper)
                )
            );
        }
        
        $this->helpers[get_class($helper)] = $helper;
        return $this;
    }
    
    public function addHelpers(array $helpers)
    {
        foreach ($helpers as $helper) {
            $this->addHelper($helper);
        }
        return $this;
    }
    
    public function prependHelper(AbstractHelper $helper)
    {
        $this->helpers = array_merge([get_class($helper) => $helper], $this->helpers);
        return $this;
    }
    
    public function invoke($methodName, array $args = [])
    {
        if (!$helper = $this->findHelper($methodName)) {
            throw new Exception\RuntimeException(
                sprintf(
                    "Couldn't find helper for method '%s'",
                    $methodName
                )
            );
        }
        return call_user_func_array([$helper, $methodName], $args);
    }
    
    public function findHelper($methodName)
    {
        if (isset($this->methods[$methodName])) {
            if (true === $this->methods[$methodName]) {
                return $this->baseHelper;
            } else {
                return $this->helpers[$this->methods[$methodName]];
            }
        }
        
        foreach ($this->helpers as $index => $helper) {
            if (method_exists($helper, $methodName)) {
                $this->methods[$methodName] = $index;
                return $helper;
            }
        }
        
        if (method_exists($this->baseHelper(), $methodName)) {
            $this->methods[$methodName] = true;
            return $this->baseHelper;
        }
        return false;
    }
    
    public function getHelper($className)
    {
        if ($this->helperExists($className)) {
            return $this->helpers[$className];
        } else {
            throw new Exception\RuntimeException(
                sprintf(
                    "Helper '%s' not found",
                    $className
                )
            );
        }
    }
    
    public function helperExists($className)
    {
        return isset($this->helpers[$className]);
    }
    
    protected function setDefaultHelpers()
    {
        foreach (self::$defaultHelpers as $className) {
            $this->addHelper(new $className($this));
        }
    }
}
