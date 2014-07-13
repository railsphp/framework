<?php
namespace Rails\Assets;

use DirectoryIterator;
use Rails\Config\Config;
use Rails\Railtie\ConfigurableRailtieTrait;

class Assets
{
    /*use ConfigurableRailtieTrait;*/
    
    // protected $paths = [];
    
    // protected $prefix = '/assets';
    
    protected $finder;
    
    protected $tempFolder;
    
    // protected $compilers = [];
    
    // protected $compressors = [

    // ];
    
    protected $config;
    
    /**
     * @param array|Config $config
     */
    public function __construct(/*$config = []*/)
    {
        $this->config = \Rails::getService('rails.config')['assets'];
    }
    
    public function setTempFolder($tempFolder)
    {
        $this->tempFolder = $tempFolder;
    }
    
    public function tempFolder()
    {
        if (!$this->tempFolder) {
            $this->tempFolder = sys_get_temp_dir();
        }
        return $this->tempFolder;
    }
    
    /**
     * $compiler may be either a string, which should be the name of a class that extends
     * Compiler\Compiler, or an array which will be used to instantiate
     * \Rails\Config\MethodConfig.
     *
     * @param string $type  The file type (or extension) the compiler is for.
     * @param string|array
     * @see \Rails\Config\MethodConfig
     */
    // public function setCompiler($type, $compiler)
    // {
        // if (!is_array($compiler) && !is_string($compiler)) {
            // throw new Exception\InvalidArgumentException(
                
            // );
        // }
        // $this->compilers[$type] = $compiler;
    // }
    
    public function getCompiler($type)
    {
        if (!isset($this->config['compilers'][$type])) {
            return false;
        }
        return $this->config['compilers'][$type];
    }
    
    /**
     * Get compilers.
     *
     * @return array
     */
    public function compilers()
    {
        return $this->config['compilers']->toArray();
    }
    
    /**
     * $methodConfig will be used to instantiate \Rails\Config\MethodConfig.
     *
     * @param string $type  The file type (or extension) the compressor is for.
     * @param array
     * @see \Rails\Config\MethodConfig
     */
    // public function setCompressor($type, array $methodConfig)
    // {
        // $this->compressors[$type] = $methodConfig;
    // }
    
    public function getCompressor($type)
    {
        if (!$compressor = $this->config['compressors']->get($type)) {
            return false;
        }
        return $compressor;
    }
    
    /**
     * Get compressors.
     *
     * @return array
     */
    public function compressors()
    {
        return $this->config['compressors']->toArray();
    }
    
    public function prefix()
    {
        return $this->config['prefix'];
    }
    
    public function addPathsFrom($basePaths)
    {
        if (!is_array($basePaths)) {
            $basePaths = [$basePaths];
        }
        
        $paths = [];
        
        foreach ($basePaths as $basePath) {
            if (!is_dir($basePath)) {
                continue;
            }
            
            $dir = new DirectoryIterator($basePath);
            
            foreach ($dir as $file) {
                if ($file->isDot() || !$file->isDir()) {
                    continue;
                }
                $paths[] = $file->getPathname();
            }
        }
        
        $this->addPaths($paths);
        return $this;
    }
    
    public function addPaths($paths)
    {
        $this->config['paths'] = array_merge($this->config['paths'], (array)$paths);
        return $this;
    }
    
    public function setPaths(array $paths)
    {
        $this->config['paths'] = $paths;
        return $this;
    }
    
    public function paths()
    {
        return $this->config['paths'];
    }
    
    # TODO: set according to configuration. For more info read Rails api.
    public function host()
    {
        return $this->config['host'];
    }
    
    // public function compressFile($file, $compile = true, $fullCompile = true)
    // {
        // if (!$file instanceof File\File) {
            // $file = $this->finder()->find($file);
            // if (!$file) {
                // return false;
            // }
        // }
        
        // if ($compile) {
            // $
        // }
        
        // return $compressedFile;
    // }
    
    // public function 
    
    public function compressContents($type, $contents)
    {
        if ($this->config['compressors']->$type->none()) {
            throw new Exception\RuntimeException(
                sprintf(
                    "Unknown compressor for type '%s'",
                    $type
                )
            );
        }
        
        $method = new \Rails\Config\MethodConfig($this->config['compressors']->$type->toArray());
        return $method->invoke($contents);
    }
    
    /**
     * Returns an array containing:
     * * `file`: The `File` object associated with the physical asset file.
     * * `tempFilePath`: Path to the cached compiled file.
     *
     * @return array
     */
    public function compileFile($file, $full = true)
    {
        if (!$file instanceof File\File) {
            $file = $this->finder()->find($file);
            if (!$file) {
                throw new Exception\RUntimeException(sprintf(
                    "Couldn't find asset file '%s'",
                    $file
                ));
            }
        }
        
        if ($full) {
            return $this->compileFileFull($file);
        } else {
            return $this->compileFileBody($file);
        }
    }
    
    public function findFile($filename)
    {
        return $this->finder()->find($filename);
    }
    
    public function finder()
    {
        if (!$this->finder) {
            $this->finder = new File\Finder($this);
        }
        return $this->finder;
    }
    
    protected function compileFileFull($file)
    {
        $tempFilePath =
            $this->tempFolder() . '/' .
            $file->subPathsPath() . $file->name() .
            ($this->config['compress'] ? '-c' : '') .
            '.' . $file->type();
        
        $processor    = new Processor\Processor($this);
        $processor->listFiles($file);
        // unlink($tempFilePath);exit;
        # Check if file has changed to recompile it or not.
        if (is_file($tempFilePath)) {
            $recompile = false;
            $tempFileMTime = filemtime($tempFilePath);
            
            foreach ($processor->files() as $subFile) {
                if (filemtime($subFile->originalFilePath()) > $tempFileMTime) {
                    $recompile = true;
                    break;
                }
            }
        } else {
            $recompile = true;
        }
        
        if ($recompile) {
            $compiledFile = $processor->compileFiles();
            $compiledFile = $this->compressCompiledFile($file->type(), $compiledFile);
            
            # Cache compiled file.
            file_put_contents($tempFilePath, $compiledFile);
        }
        
        return [
            'file'         => $file,
            'tempFilePath' => $tempFilePath
        ];
    }
    
    /**
     * Compiles the file without executing directives.
     */
    protected function compileFileBody($file)
    {
        $tempFilePath =
            $this->tempFolder() . '/' .
            $file->subPathsPath() . $file->name() . '-body' .
            ($this->config['compress'] ? '-c' : '') .
            '.' . $file->type();
        
        $recompile = true;
        
        if (is_file($tempFilePath)) {
            $tempFileMTime = filemtime($tempFilePath);
            
            if (filemtime($file->originalFilePath()) <= $tempFileMTime) {
                $recompile = false;
            }
        }
        
        if ($recompile) {
            $processor    = new Processor\Processor($this);
            $compiledFile = $processor->compileFile($file);
            $compiledFile = $this->compressCompiledFile($file->type(), $compiledFile);
            
            # Cache compiled file.
            file_put_contents($tempFilePath, $compiledFile);
        }
        
        return [
            'file'         => $file,
            'tempFilePath' => $tempFilePath
        ];
    }
    
    protected function compressCompiledFile($fileType, $contents)
    {
        if ($this->config['compress']) {
            return $this->compressContents($fileType, $contents);
        } else {
            return $contents;
        }
    }
}
