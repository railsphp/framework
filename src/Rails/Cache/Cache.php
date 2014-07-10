<?php
namespace Rails\Cache;

use Closure;
use Rails;

class Cache
{
    private $store;
    
    // public function __construct($config)
    // {
        // if (is_string($config))
            // $config = [$config];
        // else
            // $config = $config->toArray();
        
        // switch ($config[0]) {
            // case 'file_store':
                // $class = '\Rails\Cache\Store\FileStore';
                // break;
            
            // case 'mem_cached_store':
                // $class = '\Rails\Cache\Store\MemCachedStore';
                // break;
            
            // case 'null_store':
                // $class = '\Rails\Cache\Store\NullStore';
                // break;
            
            // default:
                // $class = $config[0];
                // break;
        // }
        
        // array_shift($config);
        
        // $this->store = new $class($config);
    // }
    
    public function setStore(Store\AbstractStore $store)
    {
        $this->store = $store;
    }
    
    public function read($key, array $options = [])
    {
        return $this->store->read($this->normalizeKey($key, $options), $options);
    }
    
    public function write($key, $value, array $options = [])
    {
        return $this->store->write($this->normalizeKey($key, $options), $value, $options);
    }
    
    public function delete($key, array $options = [])
    {
        return $this->store->delete($this->normalizeKey($key, $options), $options);
    }
    
    public function exists($key, array $options = [])
    {
        return $this->store->exists($this->normalizeKey($key, $options), $options);
    }
    
    public function fetch($key, $options = null, Closure $block = null)
    {
        if ($options instanceof Closure) {
            $block = $options;
            $options = [];
        }
        
        $key = $this->normalizeKey($key, $options);
        
        $value = $this->read($key, $options);
        
        if ($value === null) {
            $value = $block();
            $this->write($key, $value, $options);
        }
        return $value;
    }
    
    public function cleanup(array $options = [])
    {
        $this->store->cleanUp($options);
    }
    
    public function clear(array $options = [])
    {
        $this->store->clear($options);
    }
    
    public function deleteMatched($matcher, array $options = [])
    {
        $this->store->deleteMatched($matcher, $options);
    }
    
    public function readMulti(/*...$names*/)
    {
        $names = func_get_args();
        
        if (is_array(end($names))) {
            $options = array_pop($names);
        } else {
            $options = [];
        }
        
        $results = [];
        foreach ($names as $name) {
            if (is_array($name)) {
                $name = $this->hashToKey($name);
            }
            if (null !== ($value = $this->read($name))) {
                $results[$name] = $value;
            }
        }
        return $results;
    }
    
    public function store()
    {
        return $this->store;
    }
    
    protected function normalizeKey($key, array $options)
    {
        // if (is_string($key)) {
            // do nothing
        // } else
        if (is_array($key)) {
            $normalized = [];
            foreach ($key as $k => $v) {
                $normalized[] = $k . '=' . $v;
            }
            $key = implode('/', $normalized);
        } elseif (!is_string($key)) {
            Exception\InvalidArgumentException(
                sprintf(
                    "Cache key option must be either string or array, '%s' passed",
                    gettype($expiration)
                )
            );
        }
        
        if (isset($options['namespace'])) {
            return $options['namespace'] . ':' . $key;
        }
        return $key;
    }
}
