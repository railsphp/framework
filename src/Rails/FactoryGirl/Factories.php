<?php
namespace Rails\FactoryGirl;

class Factories
{
    protected $factories = [];
    
    public function set($name, Factory $factory)
    {
        if ($this->exists($name)) {
            throw new Exception\RuntimeException(sprintf(
                "Factory '%s' already exists",
                $name
            ));
        }
        $this->factories[$name] = $factory;
    }
    
    public function create($name, array $options, array $attributes)
    {
        $factory = new Factory($name, $options, $attributes);
        $this->set($name, $factory);
    }
    
    public function exists($name)
    {
        return isset($this->factories[$name]);
    }
    
    public function get($name)
    {
        if ($this->exists($name)) {
            return $this->factories[$name];
        }
        return null;
    }
}
