<?php
namespace Rails\Assets\Processor;

use SplFileObject;
use Rails\Assets\Assets;
use Rails\Assets\Compiler\Compiler;
use Rails\Assets\File\File;

class Processor
{
    protected $assets;
    
    protected $files = [];
    
    public function __construct(Assets $assets)
    {
        $this->assets = $assets;
    }
    
    public function assets()
    {
        return $this->assets;
    }
    
    public function files()
    {
        return $this->files;
    }
    
    public function addFile($file, $reinclude = false)
    {
        if ($reinclude || !$this->isFileAdded($file)) {
            $this->files[] = $file;
        }
    }
    
    public function isFileAdded($file)
    {
        foreach ($this->files as $addedFile) {
            if (
                $addedFile->rootPath() == $file->rootPath() &&
                $addedFile->name() == $file->name() &&
                $addedFile->type() == $file->type()
            ) {
                return true;
            }
        }
        return false;
    }
    
    public function listFiles($file)
    {
        $executor   = new Directives\Executor($file, $this);
        $directives = $this->extractDirectives($file)[0];
        $executor->execute($directives);
    }
    
    /**
     * Extract directives.
     *
     * @param File $file
     * @return array
     * @see Directives\Extractor::extract()
     */
    public function extractDirectives($file)
    {
        return Directives\Extractor::extractFromFile($file);
    }
    
    /**
     * Strip directives.
     *
     * @param File $file
     * @return string the file contents without directives.
     */
    public function stripDirectives($file)
    {
        return $this->extractDirectives($file)[1];
    }
    
    public function compileFile($file)
    {
        return $this->compileFiles([$file]);
    }
    
    public function compileFiles(array $files = null)
    {
        if (!$files) {
            $files = $this->files;
        }
        
        $compiler = new Compiler($this, current($files)->type());
        
        foreach ($files as $file) {
            $compiler->compile($file);
        }
        
        return $compiler->compiledFile();
    }
}
