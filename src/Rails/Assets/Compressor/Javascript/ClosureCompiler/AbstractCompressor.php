<?php
namespace Rails\Assets\Compressor\Javascript\ClosureCompiler;

use Rails\ServiceManager\ServiceLocatorAwareTrait;

abstract class AbstractCompressor
{
    use ServiceLocatorAwareTrait;
    
    protected $compiler;
    
    abstract protected function compress($code, array $options);
    
    public function __construct(ClosureCompiler $compiler)
    {
        $this->compiler = $compiler;
    }
}
