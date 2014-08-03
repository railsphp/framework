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
    
    /**
     * Creates a Closure that will be bound to $object, and will
     * require the file passed to it as argument. This is useful
     * when requiring files inside an object, but want it to
     * stay in the public scope only.
     *
     * @param object $object
     * @return \Closure
     */
    public static function generateFileIncluder($object)
    {
        return generateFileIncluder($object);
    }
}

/**
 * Creating a Closure inside a static method will cause it to be
 * an "static closure" which cannot be bound to any object; this
 * can be avoided by using a function.
 *
 * @param object $object
 * @return \Closure
 */
function generateFileIncluder($object) {
    $includer = function($filepath) {
        require $filepath;
    };
    return $includer->bindTo($object, $object);
}
