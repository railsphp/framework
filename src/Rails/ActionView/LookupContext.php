<?php
namespace Rails\ActionView;

class LookupContext
{
    public $paths    = [];
    
    public $prefixes = [];
    
    public $locales  = [];
    
    public $formats  = [];
    
    public $handlers = [];
    
    public function addPath($path)
    {
        array_unshift($this->paths, $path);
        return $this;
    }
    
    public function addPaths($paths)
    {
        $this->paths = array_merge($paths, $this->paths);
        return $this;
    }
}
