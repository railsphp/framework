<?php
namespace Rails\Routing\Resources;

use Closure;

class Resource
{
    use \Rails\ServiceManager\ServiceLocatorAwareTrait;
    
    protected $name;
    
    protected $path;
    
    protected $controller;
    
    protected $as;
    
    protected $param;
    
    protected $options;
    
    protected $singular;
    
    public function __construct($entities, $options = [])
    {
        $this->name       = $entities;
        $this->path       = isset($options['path']) ? $options['path'] : $this->name;
        $this->controller = isset($options['controller']) ? $options['controller'] : $this->name;
        $this->as         = isset($options['as']) ? $options['as'] : null;
        $this->param      = isset($options['param']) ? $options['param'] : 'id';
        $this->options    = $options;
    }
    
    public function controller()
    {
        return $this->controller;
    }
    
    public function path()
    {
        return $this->path;
    }
    
    public function options()
    {
        return $this->options;
    }
    
    public function param()
    {
        return $this->param;
    }
    
    public function defaultActions()
    {
        return ['index', 'create', 'new', 'show', 'update', 'destroy', 'edit'];
    }
    
    public function actions()
    {
        if (!empty($this->options['only'])) {
            return $this->options['only'];
        } elseif (!empty($this->options['except'])) {
            return array_diff($this->defaultActions(), $this->options['except']);
        } else {
            return $this->defaultActions();
        }
    }
    
    public function name()
    {
        return $this->as ?: $this->name;
    }
    
    public function plural()
    {
        return $this->name;
    }
    
    public function singular()
    {
        if (!$this->singular) {
            $this->singular = self::services()->get('inflector')->singularize($this->name);
        }
        return $this->singular;
    }
    
    public function memberName()
    {
        return $this->singular();
    }
    
    public function collectionName()
    {
        return $this->singular() == $this->plural() ? $this->plural() . 'Index' : $this->plural();
    }
    
    public function resourceScope()
    {
        return ['controller' => $this->controller];
    }
    
    public function collectionScope()
    {
        return $this->path;
    }
    
    public function memberScope()
    {
        return $this->path . '/:' . $this->param;
    }
    
    public function newScope($newPath)
    {
        return $this->path . '/' . $newPath;
    }
    
    public function nestedParam()
    {
        return $this->singular() . '_' . $this->param;
    }
    
    public function nestedScope()
    {
        return $this->path . '/:' . $this->nestedParam();
    }
}
