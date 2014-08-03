<?php
namespace Rails\FactoryGirl;

use Closure;

class Fixtures
{
    protected $fixtures = [];
    
    protected $lazyFixtures = [];
    
    public function set($namespace, $name, $value)
    {
        if ($this->exists($namespace, $name)) {
            throw new Exception\RuntimeException(sprintf(
                "Fixture '%s.%s' already exists",
                $namespace,
                $name
            ));
        }
        
        if ($value instanceof Closure) {
            $this->ensureNamespace($namespace, true);
            $this->lazyFixtures[$namespace][$name] = $value;
        } else {
            $this->ensureNamespace($namespace, false);
            $this->fixtures[$namespace][$name] = $value;
        }
    }
    
    public function get($namespace, $name)
    {
        if ($this->exists($namespace, $name)) {
            throw new Exception\RuntimeException(sprintf(
                "Fixture '%s.%s' doesn't exist",
                $namespace,
                $name
            ));
        }

        if (isset($this->fixtures[$namespace][$name])) {
            return $this->fixtures[$namespace][$name];
        } else {
            $fixture = $this->lazyFixtures[$namespace][$name]();
            $this->ensureNamespace($namespace);
            $this->fixtures[$namespace][$name] = $fixture;
            $this->lazyFixtures[$namespace][$name] = true;
            return $fixture;
        }
    }
    
    public function exists($namespace, $name)
    {
        return isset($this->fixtures[$namespace][$name]) ||
               isset($this->lazyFixtures[$namespace][$name]);
    }
    
    protected function ensureNamespace($namespace, $lazy)
    {
        if ($lazy) {
            if (!isset($this->lazyFixtures[$namespace])) {
                $this->lazyFixtures[$namespace] = [];
            }
        } else {
            if (!isset($this->fixtures[$namespace])) {
                $this->fixtures[$namespace] = [];
            }
        }
    }
}
