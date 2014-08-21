<?php
namespace Rails\FactoryGirl;

class Factory
{
    protected $name;
    
    protected $className;
    
    protected $options = [];
    
    protected $attributes = [];
    
    protected $lazyAttributes = [];
    
    /**
     * @param string $name
     * @param array $options
     * @param array $attributes
     */
    public function __construct($name, array $options, array $attributes)
    {
        $this->name = $name;
        $this->options = $options;
        $this->attributes = $attributes;
        $this->processOptions();
        $this->setLazyAttributes();
    }
    
    public function attributes()
    {
        return $this->attributeValues();
    }
    
    public function build(array $extraAttrs = [])
    {
        $attributes = array_merge($this->attributes(), $extraAttrs);
        return $this->instance($attributes);
    }
    
    public function create(array $extraAttrs = [])
    {
        $instance = $this->build($extraAttrs);
        $instance->save();
        return $instance;
    }
    
    public function extend($name, array $options, array $attributes)
    {
        $clone = clone $this;
        $clone->options = array_merge($clone->options, $options);
        $clone->attributes = array_merge($clone->attributes, $attributes);
        $this->processOptions();
        $this->setLazyAttributes();
        return $clone;
    }
    
    protected function attributeValues()
    {
        if ($this->lazyAttributes) {
            $lazy = [];
            foreach ($this->lazyAttributes as $name => $closure) {
                $lazy[$name] = $closure();
            }
            return array_merge($this->attributes, $lazy);
        } else {
            return $this->attributes;
        }
    }
    
    protected function setLazyAttributes()
    {
        foreach ($this->attributes as $name => $value) {
            if (is_callable($value)) {
                $this->lazyAttributes[$name] = $value;
                unset($this->attributes[$name]);
            }
        }
    }
    
    protected function processOptions()
    {
        $options = $this->options;
        
        if (!isset($options['className'])) {
            $this->className = ucfirst($this->name);
        } else {
            $this->className = $options['className'];
            unset($options['className']);
        }
        
        $this->options = $options;
    }
    
    protected function instance(array $attributes)
    {
        $className = $this->className;
        $instance  = new $className();
        $instance->assignAttributes($attributes, true);
        return $instance;
    }
}
