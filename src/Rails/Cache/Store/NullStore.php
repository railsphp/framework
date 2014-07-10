<?php
namespace Rails\Cache\Store;

class NullStore extends AbstractStore
{
    public function __construct(array $config = [])
    {
    }
    
    public function read($key, array $params = [])
    {
        return null;
    }
    
    public function write($key, $val, array $params)
    {
        return true;
    }
    
    public function delete($key, array $params = [])
    {
        return true;
    }
    
    public function exists($key, array $params = [])
    {
        return false;
    }
}
