<?php
namespace Rails\Routing\Route;

use Jeremeamia\SuperClosure\SerializableClosure;
use Rails\ActionDispatch\Dispatcher;
use Rails\Routing\ActionToken;

class BuiltRoute
{
    protected $path;
    
    protected $name;
    
    protected $to;
    
    protected $controller;
    
    protected $action;
    
    protected $endPoint;
    
    protected $pathRegex;
    
    # Allowed http verbs for this route.
    protected $via        = [];
    
    protected $defaults   = [];
    
    protected $vars       = [];
    
    protected $namespaces = [];
    
    protected $requiredVars   = [];
    
    protected $optionalGroups = [];
    
    protected $anchor;
    
    public static function fromArray(array $array)
    {
        $route = new self();
        foreach ($array as $name => $value) {
            $route->$name = $value;
        }
        if ($route->endPoint && substr($route->endPoint, 0, 2) == 'C:') {
            $route->endPoint = unserialize($route->endPoint);
        }
        if ($route->to) {
            $route->to = ActionToken::fromArray($route->to);
        }
        return $route;
    }
    
    // public static function fromJson($json)
    // {
        // $route = new self();
        // foreach (json_decode($json, true) as $name => $value) {
            // $route->$name = $value;
        // }
        // if ($route->endPoint && substr($route->endPoint, 0, 2) == 'C:') {
            // $route->endPoint = unserialize($route->endPoint);
        // }
        // if ($route->to) {
            // $route->to = ActionToken::fromArray($route->to);
        // }
        // return $route;
    // }
    
    public function setProperController($controller)
    {
        if ($this->controller == ':controller') {
            $this->controller = $controller;
        }
        return $this;
    }
    
    public function setProperAction($action)
    {
        if ($this->action == ':action') {
            $this->action = $action;
        }
        return $this;
    }
    
    public function controller()
    {
        return $this->controller;
    }
    
    public function action()
    {
        return $this->action;
    }
    
    public function path()
    {
        return $this->path;
    }
    
    public function pathRegex()
    {
        return $this->pathRegex;
    }
    
    public function name()
    {
        return $this->name;
    }
    
    public function via()
    {
        return $this->via;
    }
    
    public function vars()
    {
        return $this->vars;
    }
    
    public function defaults()
    {
        return $this->defaults;
    }
    
    public function requiredVars()
    {
        return $this->requiredVars;
    }
    
    public function to()
    {
        return $this->to;
    }
    
    public function namespaces()
    {
        return $this->namespaces;
    }
    
    public function optionalGroups()
    {
        return $this->optionalGroups;
    }
    
    public function endPoint()
    {
        if (!$this->endPoint) {
            $this->endPoint = new Dispatcher($this->to);
        }
        return $this->endPoint;
    }
    
    public function build()
    {
        return $this;
    }
    
    public function toArray()
    {
        $vars = get_object_vars($this);
        if ($vars['endPoint'] && $vars['endPoint'] instanceof SerializableClosure) {
            $vars['endPoint'] = serialize($vars['endPoint']);
        }
        if ($vars['to']) {
            $vars['to'] = $vars['to']->toArray();
        }
        if ($vars['endPoint'] && $vars['endPoint'] instanceof Dispatcher) {
            $vars['endPoint'] = null;
        }
        return $vars;
    }
}
