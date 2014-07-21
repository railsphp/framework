<?php
namespace Rails\Cache\Store;

use Rails;
use Rails\Toolbox;
use Rails\Cache\Store\FileStore\Entry;

/**
 * This class is intended to be used only by Rails.
 */
class FileStore extends AbstractStore
{
    protected $basePath;
    
    public function __construct($basePath = null)
    {
        if (!$basePath) {
            $basePath = sys_get_temp_dir();
        }
        $this->setBasePath($basePath);
    }
    
    public function basePath()
    {
        return $this->basePath;
    }
    
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
        
        if (!is_dir($basePath)) {
            mkdir($basePath, 0775, true);
        }
    }
    
    public function read($key, array $options = [])
    {
        return $this->getEntry($key, $options)->read();
    }
    
    public function write($key, $value, array $options = [])
    {
        return $this->getEntry($key, $options)->write($value);
    }
    
    public function exists($key, array $options = [])
    {
        return $this->getEntry($key, $options)->fileExists();
    }
    
    public function delete($key, array $options = [])
    {
        return $this->getEntry($key, $options)->delete();
    }
    
    public function cleanUp(array $options = [])
    {
        foreach (Toolbox\FileTools::listFilesRecursive($this->basePath) as $finfo) {
            $key   = urldecode($finfo->getBaseName());
            $entry = $this->getEntry($key, []);
            
            if ($entry->expired()) {
                $entry->delete();
            }
        }
    }
    
    public function clear(array $options = [])
    {
        Toolbox\FileTools::emptyDirRecursive($this->basePath);
    }
    
    public function deleteMatched($matcher, array $options = [])
    {
        $allFiles = Toolbox\FileTools::searchFile($this->basePath);
        foreach ($allFiles as $file) {
            $key = urldecode(substr($file, strrpos($file, DIRECTORY_SEPARATOR) + 1));
            if (preg_match($matcher, $key)) {
                $this->getEntry($key, [])->delete();
            }
        }
        
        foreach (Toolbox\FileTools::listFilesRecursive($this->basePath) as $finfo) {
            $key   = $finfo->getBaseName();
            $entry = $this->getEntry($key, []);
            
            if ($entry->expired()) {
                $entry->delete();
            }
        }
    }
    
    protected function getEntry($key, array $options)
    {
        return new Entry($this, $key, $options);
    }
}
