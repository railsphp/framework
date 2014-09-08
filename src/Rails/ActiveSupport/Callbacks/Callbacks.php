<?php
namespace Rails\ActiveSupport\Callbacks;

use Closure;
use Rails\ActiveSupport\Exception;

class Callbacks
{
    protected $skips     = [];
    
    protected $chains    = [];
    
    protected $callbacks = [];
    
    /**
     * Default runner.
     *
     * @var object
     */
    protected $runner;
    
    public function setRunner($runner)
    {
        $this->runner = $runner;
    }
    
    /**
     * Defines callbacks chains.
     *
     * Although the name is similar to "setCallbacks", the funcionality is
     * different. Callback chains must be defined before callbacks can be set for
     * a chain with setCallbacks().
     *
     * Available options are:
     * * `terminator` - a string that represents the name of a method to which the
     * returning value of each before-callback is passed to be validated. If the terminator
     * returns `false`, the before-callback queue is halted.
     * A passing true or false as terminator will cause the returning value to be tested for
     * equality against the terminator. E.g. if `true` is passed as terminator, and the value
     * of one of the before-callbacks returns an empty value, the chain is terminated.
     * * `skipAfterCallbacksIfTerminated` - if true is passed, and `terminator` is
     * defined and returns `false`, the `around` and `after` callbacks (if any) won't be ran.
     *
     * <pre>
     * $callbacks = new Callbacks();
     * $callbacks->defineCallbacks([
     *     'filters' => [
     *         'terminator' => 'responseSet',
     *         'skipAfterCallbacksIfTerminated' => true
     *     ],
     *     // Options can also be skipped
     *     'save'
     * ]);
     * </pre>
     *
     * @param array $callbacks
     * @throws Exception\RuntimeException if the chain has already been defined
     */
    public function defineCallbacks(array $callbacks)
    {
        $defaultOptions = [
            'scope'      => ['kind'],
            'terminator' => null,
            'skipAfterCallbacksIfTerminated' => false,
        ];
        
        foreach ($callbacks as $name => $options) {
            if (is_int($name)) {
                $name    = $options;
                $options = [];
            }
            if (isset($this->chains[$name])) {
                throw new Exception\RuntimeException(
                    "Callback '%s' already defined",
                    $name
                );
            }
            $options = array_merge($defaultOptions, $options);
            $this->chains[$name] = $options;
        }
        return $this;
    }
    
    /**
     * @see setOrSkipCallback()
     */
    public function setCallbacks($name, array $callablesByKind)
    {
        $this->setOrSkipCallback('set', $name, $callablesByKind);
    }
    
    /**
     * @see setOrSkipCallback()
     */
    public function skipCallbacks($name, array $callablesByKind)
    {
        $this->setOrSkipCallback('skip', $name, $callablesByKind);
    }
    
    /**
     * @param string $name    the name of the callback chain
     * @param object $runner  the object for which callbacks will run
     * @param Closure $block  piece of code that callbacks will wrap around
     * @throws Exception\InvalidArgumentException if the chain named $name doesn't exist
     */
    public function run($name, Closure $block = null, $runner = null)
    {
        if (!$this->isCallbackDefined($name)) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    'Trying to run undefined callbacks \'%s\'',
                    $name
                )
            );
        }
        
        if (isset($this->callbacks[$name])) {
            if (!$runner) {
                $runner = $this->runner;
            }
            
            if (
                !$this->runCallbacks($name, 'before', $runner)          &&
                $this->chains[$name]['skipAfterCallbacksIfTerminated']
            ) {
                return false;
            }
            
            $ret = $this->runAround($name, $runner, $block);
            $this->runCallbacks($name, 'after', $runner);
            return $ret;
        } elseif ($block) {
            $ret = $block();
            if (null === $ret) {
                return true;
            } else {
                return (bool)$ret;
            }
        }
        return true;
    }
    
    /**
     * Set conditions to run the callback in the "if" option. It can be an array holding
     * many conditions, or a single condition. Conditions can be:
     * * string: The name of a method that will be called for the *runner* object.
     *           The scope of the method doesn't matter.
     * * callable: Any kind of callable. However, if you're setting a callable like
     *             `[$object, 'method']`, you will have to wrap this inside another array,
     *             like so: `[ [$object, 'method'] ]`, otherwise this will cause errors.
     */
    protected function setOrSkipCallback($do, $name, array $callablesByKind)
    {
        if (!$this->isCallbackDefined($name)) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    'Trying to set callbacks for undefined chain \'%s\'',
                    $name
                )
            );
        }
        
        if ($do == 'set') {
            $prop = 'callbacks';
        } else {
            $prop = 'skips';
        }
        
        if (!isset($this->{$prop}[$name])) {
            $this->{$prop}[$name] = [
                'before' => [],
                'around' => [],
                'after'  => []
            ];
        }
        
        foreach ($callablesByKind as $kind => $callbacks) {
            if (!isset($this->{$prop}[$name][$kind])) {
                throw new Exception\InvalidArgumentException(
                    sprintf(
                        "Trying to set callback in invalid filter '%s'",
                        $kind
                    )
                );
            }
            
            list ($append, $prepend) = $this->normalizeOptions($callbacks);
            $this->{$prop}[$name][$kind] = array_merge(
                $prepend,
                $this->{$prop}[$name][$kind],
                $append
            );
        }
    }
    
    protected function runCallbacks($name, $kind, $runner)
    {
        /**
         * $resp is used by isTerminated().
         */
        $invoker = function($method, $resp = null) {
            if (func_num_args() > 1) {
                return $this->$method($resp);
            } else {
                return $this->$method();
            }
        };
        $invoker = $invoker->bindTo($runner, get_class($runner));
        
        $callbacks = $this->callbacks[$name][$kind];
        
        foreach ($callbacks as $callable => $options) {
            # Case: [new Audit(), 'if' => ...]
            # can also pass Closures
            if (is_int($callable)) {
                $object = array_shift($options);
                if ($this->canRun($options)) {
                    if ($object instanceof Closure) {
                        $resp = $object($runner);
                    } else {
                        $method = $this->getScopeMethod($name, $kind);
                        $resp   = $object->$method($runner);
                    }
                } else {
                    continue;
                }
            } else {
                # Cases:
                # [ 'beforeMeth', 'afterMethod' => ['if' => ...] ]
                if ($this->canRun($options, $name, $kind, $callable, $invoker)) {
                    $resp = $invoker($callable);
                } else {
                    continue;
                }
            }
                
            if ($kind == 'before' && $this->isTerminated($name, $resp, $invoker)) {
                return false;
            }
        }
        
        return true;
    }
    
    protected function runAround($name, $runner, Closure $block = null)
    {
        if (!$this->callbacks[$name]['around']) {
            if ($block) {
                return $block();
            } else {
                return true;
            }
        }
        
        $invoker = function($method, $yielder = null) {
            if ($yielder) {
                return $this->$method($yielder);
            } else {
                return $this->$method();
            }
        };
        $invoker = $invoker->bindTo($runner, get_class($runner));
        
        $first   = true;
        $resp    = true;
        $yielder = function() use (
            $runner,
            $invoker,
            $name,
            $block,
            &$yielder,
            &$options,
            &$first,
            &$resp
        ) {
            if ($first) {
                $options  = reset($this->callbacks[$name]['around']);
                $callable = key($this->callbacks[$name]['around']);
                $first    = false;
            } else {
                $options  = next($this->callbacks[$name]['around']);
                $callable = key($this->callbacks[$name]['around']);
            }
            
            if ($callable !== null) {
                if (is_int($callable)) {
                    $object = array_shift($options);
                    
                    if ($this->canRun($options)) {
                        if ($object instanceof Closure) {
                            $object($runner, $yielder);
                        } else {
                            $method = $this->getScopeMethod($name, $kind);
                            $object->$method($runner, $yielder);
                        }
                    }
                } else {
                    if ($this->canRun($options, $name, 'around', $callable, $invoker)) {
                        $invoker($callable, $yielder);
                    }
                }
            } else {
                if ($block) {
                    $resp = $block();
                }
            }
        };
        
        $yielder();
        
        return $resp;
    }
    
    /**
     * If this method returns true, the callback chain is terminated.
     *
     * @return bool
     */
    protected function isTerminated($name, $resp, $invoker)
    {
        if ($this->chains[$name]['terminator']) {
            if ($this->chains[$name]['terminator'] === true) {
                return $resp == true;
            } elseif ($this->chains[$name]['terminator'] === false) {
                return $resp == false;
            }
            
            $callable = $this->chains[$name]['terminator'];
            
            if (is_string($callable)) {
                return (bool)$invoker($callable, $resp);
            } else {
                return (bool)$callable($resp);
            }
        }
        return false;
    }
    
    /**
     * "if" conditions passed to options in skipCallbacks must be callables
     * that must return boolean.
     */
    protected function canRun(array $options, $chainName = null, $kind = null, $callbackName = null, $invoker = null)
    {
        # Closures or other objects will cause $callbackName to be int. A skip can't be registered
        # to them because their callbacks have no "name". They can be skipped only with the "if" option.
        if ($chainName && isset($this->skips[$chainName][$kind][$callbackName])) {
            if (!isset($this->skips[$chainName][$kind][$callbackName]['if'])) {
                # No 'if' option passed.
                return false;
            }
            
            $conditions = $this->skips[$chainName][$kind][$callbackName]['if'];
            foreach ($conditions as $condition) {
                if (is_string($condition)) {
                    if (!(!$invoker($condition))) {
                        return false;
                    }
                } else {
                    if (!(call_user_func($condition))) {
                        return false;
                    }
                }
            }
        } elseif (isset($options['if'])) {
            foreach ($options['if'] as $condition) {
                if (is_string($condition)) {
                    if (!$invoker($condition)) {
                        return false;
                    }
                } else {
                    if (!call_user_func($condition)) {
                        return false;
                    }
                }
            }
        }
        return true;
    }
    
    protected function normalizeOptions(array $callbacks)
    {
        $append  = [];
        $prepend = [];
        
        foreach ($callbacks as $callable => $options) {
            if (is_int($callable)) {
                if (is_string($options)) {
                    $callable = $options;
                    $options  = [];
                } elseif (is_object($options)) {
                    $options = [$options];
                } elseif (!is_array($options)) {
                    throw new Exception\InvalidArgumentException(
                        sprintf(
                            "Options must be either string (if none), array or object, %s passed",
                            gettype($options)
                        )
                    );
                }
            }
            
            if (isset($options['if']) && !is_array($options['if'])) {
                $options['if'] = [ $options['if'] ];
            }
            
            if (is_string($callable)) {
                if (!empty($options['prepend'])) {
                    $prepend[$callable] = $options;
                } else {
                    $append[$callable]  = $options;
                }
            } else {
                if (!empty($options['prepend'])) {
                    $prepend[] = $options;
                } else {
                    $append[]  = $options;
                }
            }
        }
        
        return [$append, $prepend];
    }
    
    protected function getScopeMethod($name, $kind)
    {
        $scopes = $this->chains[$name]['scope'];
        
        switch (current($scopes)) {
            case 'kind':
                $first  = $kind;
                $second = $name;
                break;
            
            case 'name':
                $first  = $name;
                $second = $kind;
                break;
            
            default:
                throw new Exception\InvalidArgumentException(
                    sprintf(
                        "Invalid option '%s' for callback scope",
                        current($scopes)
                    )
                );
                break;
        }
        
        if (next($scopes)) {
            return $first . ucfirst($second);
        } else {
            return $first;
        }
    }
    
    protected function isCallbackDefined($name)
    {
        return array_key_exists($name, $this->chains);
    }
}
