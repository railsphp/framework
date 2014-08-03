<?php
namespace Rails\FactoryGirl;

use Closure;

class Sequence
{
    protected $count = 0;
    
    protected $pattern;
    
    public function __construct($pattern)
    {
        $this->setPattern($pattern);
    }
    
    public function setPattern($pattern)
    {
        if (!is_string($pattern) && !$pattern instanceof Closure) {
            throw new Exception\InvalidArgumentException(sprintf(
                "Pattern must be either string or Closure, %s passed",
                gettype($pattern)
            ));
        }
        $this->pattern = $pattern;
    }
    
    public function value()
    {
        $pattern = $this->pattern;
        if (is_string($pattern)) {
            $value = sprintf($pattern, $this->count);
        } else {
            $value = $pattern($this->count);
        }
        $this->count++;
        return $value;
    }
    
    public function __invoke()
    {
        return $this->value();
    }
}
