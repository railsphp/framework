<?php
namespace Rails\Assets\File;

class File
{
    protected $finder;
    protected $rootPath;
    protected $name;
    protected $extensions = [];
    protected $subPaths = [];
    
    /**
     * Full path to original file.
     */
    protected $originalFilePath;
    
    protected $processor;
    
    /**
     * @param string $subPath
     */
    public function __construct(
        $finder,
        $rootPath,
        $name,
        array $extensions,
        $subPath,
        $originalFilePath
    ) {
        $this->finder           = $finder;
        $this->rootPath         = $rootPath;
        $this->name             = $name;
        $this->extensions       = $extensions;
        $this->originalFilePath = str_replace('\\', '/', $originalFilePath);
        if ($subPath) {
            $this->subPaths = explode('/', $subPath);
        }
    }
    
    public function setProcessor($processor)
    {
        return $this->processor = $processor;
    }
    
    public function processor()
    {
        return $this->processor;
    }
    
    /**
     * File type extension (e.g. css, js).
     */
    public function type()
    {
        return $this->extensions[0];
    }
    
    public function finder()
    {
        return $this->finder;
    }
    
    public function rootPath()
    {
        return $this->rootPath;
    }
    
    public function name()
    {
        return $this->name;
    }
    
    /**
     * Compiler extensions (e.g. scss, coffee).
     */
    public function compilers()
    {
        return array_slice($this->extensions, 1);
    }
    
    public function extensions()
    {
        return $this->extensions;
    }
    
    # Consider renaming to realFilePath();
    public function originalFilePath()
    {
        return $this->originalFilePath;
    }
    
    public function url()
    {
        return $this->finder->assets()->prefix() . '/' .
               $this->subPathsPath() .
               $this->name . '.' .
               $this->type();
    }
    
    /**
     * Sub paths as path.
     * If this file has no subpaths, empty string is returned.
     * Otherwise, the subpaths are returned imploded and with trailing slash.
     */
    public function subPathsPath()
    {
        return $this->subPaths ? implode(DIRECTORY_SEPARATOR, $this->subPaths) . DIRECTORY_SEPARATOR : '';
    }
    
    public function logicalPath()
    {
        return $this->subPathsPath() . $this->name() . '.' . $this->type();
    }
    
    public function fullDir()
    {
        if ($this->subPaths) {
            return $this->rootPath() . DIRECTORY_SEPARATOR . $this->subPathsPath();
        } else {
            return $this->rootPath();
        }
    }
}
