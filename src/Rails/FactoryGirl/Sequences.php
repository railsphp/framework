<?php
namespace Rails\FactoryGirl;

class Sequences
{
    protected $sequences = [];
    
    public function set($name, $pattern)
    {
        if ($this->exists($name)) {
            throw new Exception\RuntimeException(sprintf(
                "Sequence '%s' already exists",
                $name
            ));
        }
        $this->sequences[$name] = new Sequence($pattern);
    }
    
    public function getValue($name)
    {
        if (!$this->exists($name)) {
            throw new Exception\RuntimeException(sprintf(
                "Sequence '%s' doesn't exists",
                $name
            ));
        }
        return $this->sequences[$name]();
    }
    
    public function get($name)
    {
        if (!$this->exists($name)) {
            throw new Exception\RuntimeException(sprintf(
                "Sequence '%s' doesn't exists",
                $name
            ));
        }
        return $this->sequences[$name];
    }
    
    public function exists($name)
    {
        return isset($this->sequences[$name]);
    }
}
