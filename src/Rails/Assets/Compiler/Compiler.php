<?php
namespace Rails\Assets\Compiler;

use Rails\Assets\Exception;
use Rails\Config\MethodConfig;

class Compiler
{
    protected $processor;
    
    protected $type;
    
    protected $compiledFile = [];
    
    public function __construct($processor, $type)
    {
        $this->processor = $processor;
        $this->type      = $type;
    }
    
    public function compiledFile()
    {
        $compiler = $this->getCompiler($this->type);
        return implode($compiler::fileJoiner(), $this->compiledFile);
    }
    
    public function compile($file)
    {
        if (!$extensions = $file->compilers()) {
            $this->compiledFile[] = $this->processor->stripDirectives($file);
        } else {
            $contents = $this->processor->stripDirectives($file);
            
            foreach ($file->compilers() as $extension) {
                $compiler = $this->getCompiler($extension);
                
                if (is_array($compiler)) {
                    $method = new MethodConfig($compiler);
                    $contents = $method->invoke($contents);
                } else {
                    $contents = $compiler::compile($contents, $file);
                }
            }
            
            $this->compiledFile[] = $contents;
        }
    }
    
    protected function getCompiler($extension)
    {
        if (!$compiler = $this->processor->assets()->getCompiler($extension)) {
            throw new Exception\RuntimeException(
                sprintf("Compiler for '%s' not defined", $extension)
            );
        }
        return $compiler;
    }
}
