<?php
namespace Rails\Toolbox;

class ClassTools
{
    /**
     * @param string|object $class
     */
    public static function getClassName($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }
        
        if (is_int($pos = strpos($class, '\\'))) {
            return substr($class, $pos + 1);
        } else {
            return $class;
        }
    }
    
    /**
     * @param string|object $class
     */
    public static function getNamespace($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }
        
        if (is_int($pos = strrpos($class, '\\'))) {
            return substr($class, 0, $pos);
        } else {
            return '';
        }
    }
}
