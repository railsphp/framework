<?php
namespace Rails\ActiveModel\Callbacks;

use Closure;
use Rails\ActiveSupport\Callbacks\Callbacks;

trait CallbackUsableTrait
{
    protected $callbacks;
    
    public function runCallbacks($name, Closure $block = null)
    {
        // $this->setUpCallbacks();
        return $this->getCallbacks()->run($name, $block, $this);
    }
    
    /**
     * Returns the Callbacks object. If it doesn't exist, it is
     * created.
     */
    public function getCallbacks()
    {
        if (!$this->callbacks) {
            
            // $options   = [
                // 'scope'         => ['kind', 'name'],
                // 'terminator'    => false,
                // 'skipAfterCallbacksIfTerminated' => true
            // ];
            
            // $callbacks->defineCallbacks([
                // 'create'        => $options,
                // 'save'          => $options,
                // 'update'        => $options,
                // 'destroy'       => $options,
                // 'validation'    => $options,
            // ]);
            
            // foreach ($this->getAllCallbacks() as $kind => $callbacks) {
                // $callbacks->setCallbacks($kind, $callbacks);
            // }
            
            $this->callbacks = new Callbacks();
        }
        return $this->callbacks;
    }
    
    protected function callbacks()
    {
        return [];
    }
    
    protected function getAllCallbacks()
    {
        # TODO: Get all callbacks from all methods ending in Callbacks
        $callbacks = $this->callbacks();
        
        $reflection = new \ReflectionClass($this);
        $forbiddenDeclarers = [
            'Rails\ActiveModel\Base',
            'Rails\ActiveRecord\Base',
            'Rails\ActiveRecord\Persistence\PersistedModel',
        ];
        
        foreach ($reflection->getMethods() as $method) {
            $methodName = $method->getName();
            if (
                strpos($methodName, 'Callbacks') === strlen($methodName) - 9 &&
                !in_array($method->getDeclaringClass()->getName(), $forbiddenDeclarers)
            ) {
                $callbacks = array_merge_recursive($callbacks, $this->$methodName());
            }
        }
        
        return $callbacks;
    }
}
