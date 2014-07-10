<?php
namespace Rails\Config;

class Path
{
    protected $paths     = [];
    
    protected $basePaths = [];
    
    public function __construct($paths, $basePaths = null)
    {
        $this->paths     = is_array($paths) ? $paths : [$paths];
        $this->basePaths = is_array($basePaths) ? $basePaths : [$basePaths];
    }
    
    public function __toString()
    {
        return $this->fullPath();
    }
    
    public function toString()
    {
        return $this->fullPath();
    }
    
    public function path()
    {
        return current($this->paths);
    }
    
    public function paths()
    {
        return $this->paths;
    }
    
    public function addPath($path)
    {
        $this->paths[] = $path;
        return $this;
    }
    
    public function prependPath($path)
    {
        array_unshift($this->paths, $path);
        return $this;
    }
    
    public function setPaths(array $paths)
    {
        $this->paths = $paths;
        return $this;
    }
    
    public function basePaths()
    {
        return $this->basePaths;
    }
    
    public function addBasePath($basePath)
    {
        $this->basePaths[] = $basePath;
        return $this;
    }
    
    public function prependBasePath($basePath)
    {
        array_unshift($this->basePaths, $basePath);
        return $this;
    }
    
    public function setBasePaths(array $basePaths)
    {
        $this->basePaths = $basePaths;
        return $this;
    }
    
    public function fullPath()
    {
        $basePath = implode(DIRECTORY_SEPARATOR, $this->basePaths);
        if ($basePath) {
            $basePath .= DIRECTORY_SEPARATOR;
        }
        return $basePath . implode(DIRECTORY_SEPARATOR, $this->paths());
    }
    
    /**
     * Concats fullPath with additional sub paths (to point to a file,
     * for example). This is therefore intended to be used if this Path
     * points to a folder.
     *
     * @param string $paths  paths to concat to the fullPath
     * @return string
     */
    public function expand(/*...$paths*/)
    {
        return $this->fullPath() . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, func_get_args());
    }
    
    /**
     * Same as expand(), except that extend() clones the current path
     * and appends $paths to its paths, and returns the object instead of
     * a string.
     *
     * @param string $paths  paths to append to the fullPath
     * @return Path
     */
    public function extend(/*...$paths*/)
    {
        $extended = clone $this;
        $extended->paths = array_merge($extended->paths, func_get_args());
        return $extended;
    }
}
