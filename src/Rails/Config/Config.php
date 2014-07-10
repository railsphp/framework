<?php
namespace Rails\Config;

class Config implements \IteratorAggregate
{
    protected $container = [];
    
    protected $strict;
    
    protected $frozen;
    
    function getIterator()
    {
        return new \ArrayIterator($this->container);
    }
    
    public function __construct(array $config = [], $strict = false, $frozen = false)
    {
        if ($config) {
            $this->add($config);
        }
        $this->strict = $strict;
        $this->frozen = $frozen;
    }
    
    public function __set($prop, $value)
    {
        return $this->set($prop, $value);
    }
    
    public function __get($prop)
    {
        // if ($this->strict) {
            return $this->get($prop);
        // } else {
            // return $this->fetch($prop);
        // }
    }
    
    public function __isset($prop)
    {
        return $this->exists($prop);
    }
    
    public function add(array $config)
    {
        foreach ($config as $prop => $value) {
            $this->set($prop, $value);
        }
    }
    
    public function set($prop, $value)
    {
        if (is_array($value)) {
            $this->container[$prop] = new self($value);
        } else {
            $this->container[$prop] = $value;
        }
        return $this;
    }
    
    public function get($prop)
    {
        if (array_key_exists($prop, $this->container)) {
            return $this->container[$prop];
        }
        
        // if ($this->strict) {
            throw new \RuntimeException(
                sprintf(
                    "Trying to get undefined index '%s'",
                    $prop
                )
            );
        // }
        
        return null;
    }
    
    public function fetch($prop)
    {
        if (!$this->exists($prop)) {
            $this->set($prop, new static());
        }
        return $this->get($prop);
    }
    
    public function delete($prop)
    {
        if ($this->strict) {
            $value = $this->get($prop);
        } else {
            $value = $this->fetch($prop);
        }
        unset($this->container[$prop]);
        return $value;
    }
    
    public function exists($key)
    {
        return array_key_exists($key, $this->container);
    }
    
    public function merge(array $other)
    {
        $this->container = array_merge($this->container, $other);
        return $this;
    }
    
    public function toArray()
    {
        return $this->container;
    }
    
    public function includes($value)
    {
        return in_array($value, $this->container);
    }
    
    public function keys()
    {
        return array_keys($this->container);
    }
    
    /**
     * Check if the container is not empty.
     *
     * @return bool
     * @see blank()
     */
    public function any()
    {
        return (bool)$this->container;
    }
    
    /**
     * Same as `any()`. The difference would be the context on which one or
     * another is used: `any()` would mean that the values make a list of
     * a items, while `blank()` would mean that the values make a hash.
     *
     * @return bool
     */
    public function blank()
    {
        return (bool)$this->container;
    }
    
    /**
     * Check if the container is empty.
     *
     * @return bool
     */
    public function none()
    {
        return !$this->any();
    }
}
