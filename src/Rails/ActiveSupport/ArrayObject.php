<?php
namespace Rails\ActiveSupport;

class ArrayObject extends \ArrayObject
{
    public function unshift(/*...$vars*/)
    {
        $newArray = $this->getArrayCopy();
        call_user_func_array('array_unshift', array_merge([$newArray], func_get_args()));
        $this->exchangeArray($newArray);
        return $this;
    }
    
    public function shift()
    {
        $oldArray = $this->getArrayCopy();
        $value    = array_shift($oldArray);
        $this->exchangeArray($oldArray);
        return $value;
    }
    
    public function pop()
    {
        $oldArray = $this->getArrayCopy();
        $value    = array_pop($oldArray);
        $this->exchangeArray($oldArray);
        return $value;
    }
    
    public function keys()
    {
        return array_keys($this->getArrayCopy());
    }
    
    public function replace($input)
    {
        return $this->exchangeArray($input);
    }
    
    public function merge($other, $recursive = false)
    {
        $current = $this->getArrayCopy();
        if ($recursive) {
            $current = array_merge_recursive($current, $other);
        } else {
            $current = array_merge($current, $other);
        }
        $this->exchangeArray($current);
        return $this;
    }
    
    public function toArray()
    {
        return $this->getArrayCopy();
    }
}
