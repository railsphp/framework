<?php
namespace Rails\ActionView\Template;

use Rails\ActionView\Exception;

class Assigns
{
    protected $assigns = [];
    
    protected $strict  = false;
    
    public function __construct(array $assigns = [])
    {
        if ($assigns) {
            $this->setAssigns($assigns);
        }
    }
    
    public function setStrict($strict)
    {
        $this->strict = (bool)$strict;
    }
    
    public function setAssigns(array $assigns)
    {
        $this->assigns = $assigns;
        return $this;
    }
    
    public function assigns()
    {
        return $this->assigns;
    }
    
    public function set($name, $value)
    {
        return $this->assigns[$name] = $value;
    }
    
    public function get($name)
    {
        if (!$this->exists($name)) {
            if ($this->strict) {
                throw new Exception\RuntimeException(
                    sprintf("Assign '%s' doesn't exist", $name)
                );
            } else {
                return null;
            }
        }
        return $this->assigns[$name];
    }
    
    public function fetch($name, $fallback)
    {
        if (!$this->exists($name)) {
            if (is_callable($fallback)) {
                $fallback = $fallback();
            }
            $this->set($name, $fallback);
        }
        return $this->get($name);
    }
    
    public function exists($name)
    {
        return array_key_exists($name, $this->assigns);
    }
}
