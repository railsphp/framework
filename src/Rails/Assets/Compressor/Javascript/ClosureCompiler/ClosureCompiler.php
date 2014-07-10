<?php
namespace Rails\Assets\Compressor\Javascript\ClosureCompiler;

class ClosureCompiler
{
    protected $options;
    
    static public function compressCode($code, array $options = [])
    {
        $obj = new static($options);
        return $obj->compress($code);
    }
    
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }
    
    /**
     * @param string $code
     * @return string
     */
    public function compress($code)
    {
        if (!empty($this->options['jarFile'])) {
            $compressor = new JarCompressor($this);
        } else {
            $compressor = new ApiCompressor($this);
        }
        return $compressor->compress($code, $this->options);
    }
}
