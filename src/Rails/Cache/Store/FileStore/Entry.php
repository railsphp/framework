<?php
namespace Rails\Cache\Store\FileStore;

use Rails\Cache\Store\FileStore;

class Entry
{
    protected $key;
    
    protected $path;
    
    protected $value;
    
    protected $createdAt = 0;
    
    protected $ttl = 0;
    
    protected $store;
    
    public function __construct(FileStore $store, $key, array $options = [])
    {
        $this->store   = $store;
        $this->key     = $key;
        $this->keyHash = $this->hashKey($key);
        
        if (isset($options['expiresIn'])) {
            $this->ttl = $options['expiresIn'];
        }
    }
    
    public function read()
    {
        $this->readFile();
        return $this->value;
    }
    
    public function write($value)
    {
        $header = [];
    
        if (is_string($value)) {
            $header['serialized'] = false;
        } elseif (is_scalar($value)) {
            $header['value']      = $value;
            $header['serialized'] = false;
            $value = null;
        } else {
            $value = serialize($value);
            $header['serialized'] = true;
        }
        
        $header['ttl'] = $this->ttl;
        $header['createdAt'] = time();
        
        if (!is_dir($this->path())) {
            mkdir($this->path(), 0775, true);
        }
        
        return (bool)file_put_contents($this->filePath(), json_encode($header) . "\n" . $value);
    }
    
    public function delete()
    {
        $this->value = null;
        return $this->deleteFile();
    }
    
    public function value()
    {
        return $this->value;
    }
    
    public function expired()
    {
        if ($this->ttl && ($this->createdAt + $this->ttl) < time()) {
            return true;
        }
        return false;
    }
    
    public function fileExists()
    {
        return is_file($this->filePath());
    }
    
    public function errorHandler()
    {
        // $this->error
    }
    
    protected function readFile()
    {
        if (!$this->fileExists()) {
            $this->value = null;
            return;
        }
        
        try {
            $fileContents = file_get_contents($this->filePath());
        } catch (\Exception $e) {
            $this->value = null;
            return;
        }
        
        $this->parseContents($fileContents);
        
        if ($this->expired()) {
            $this->delete();
        }
    }
    
    protected function parseContents($fileContents)
    {
        $parts  = explode("\n", $fileContents, 2);
        $header = json_decode($parts[0], true);
        
        if ($header['serialized']) {
            $this->value = unserialize($parts[1]);
        } else {
            $this->value = array_key_exists('value', $header) ?
                            $header['value'] :
                            $parts[1];
        }
        $this->createdAt = $header['createdAt'];
        $this->ttl       = $header['ttl'];
    }
    
    protected function deleteFile()
    {
        try {
            unlink($this->filePath());
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }
    
    protected function filePath()
    {
        return $this->path() . '/' . urlencode($this->key);
    }
    
    protected function hashKey($key)
    {
        return sprintf("%u", crc32($key));
    }
    
    protected function path()
    {
        if (!$this->path) {
            $this->path = $this->generatePath($this->key);
        }
        return $this->path;
    }
    
    protected function generatePath($key)
    {
        return $this->basePath() . '/' . substr($this->keyHash, 0, 3) . '/' . substr($this->keyHash, 2, 3);
    }
    
    protected function basePath()
    {
        return $this->store->basePath();
    }
}
