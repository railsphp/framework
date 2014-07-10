<?php
namespace Rails\Config;

use ReflectionClass;

class MethodConfig
{
    /**
     * Mandatory options:
     * * class: class name that holds the method.
     * * method: name of the method to invoke from `class`.
     *
     * Optionals:
     * * static: call the method statically. Defaults to false.
     * * constructArgs: an array of arguments to pass to the constructor (if non-static).
     * * invokeArgs: an array of arguments to pass when the method is invoked. They will be appended
     *              to the arguments passed to `invoke()`.
     */
    protected $options;
    
    /**
     * Must pass both 'class' and 'method' as options.
     */
    public function __construct(array $options)
    {
        $this->options = $this->normalizeOptions($options);
    }
    
    public function __invoke()
    {
        return call_user_func_array([$this, 'invoke'], func_ge_args());
    }
    
    public function invoke(/*...$args*/)
    {
        $args = func_get_args();
        $options = $this->options;
        
        if ($options['static']) {
            return call_user_func_array(
                $options['class'] . '::' . $options['method'],
                array_merge($args, $options['invokeArgs'])
            );
        } else {
            if ($options['constructArgs']) {
                $refl     = new ReflectionClass($options['class']);
                $instance = $refl->newInstanceArgs($options['constructArgs']);
            } else {
                $instance = new $options['class'];
            }
            
            return call_user_func_array(
                [$instance, $options['method']],
                array_merge($args, $options['invokeArgs'])
            );
        }
    }
    
    protected function normalizeOptions(array $options)
    {
        if (!isset($options['class'])) {
            throw new Exception\BadMethodCallException(
                "Missing option 'class'"
            );
        } elseif (!isset($options['method'])) {
            throw new Exception\BadMethodCallException(
                "Missing option 'method'"
            );
        }
        
        if (!isset($options['static'])) {
            $options['static'] = false;
        }
        if (!isset($options['constructArgs'])) {
            $options['constructArgs'] = [];
        }
        if (!isset($options['invokeArgs'])) {
            $options['invokeArgs'] = [];
        }
        
        return $options;
    }
}
