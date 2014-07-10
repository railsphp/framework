<?php
namespace Rails\Config;

class Env
{
    protected $env;
    
    public function __construct($env)
    {
        $this->env = $env;
    }
    
    public function __get($method, $args)
    {
        return $method == $env;
    }
}
