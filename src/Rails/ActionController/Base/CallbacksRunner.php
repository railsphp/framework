<?php
namespace Rails\ActionController\Base;

class CallbacksRunner
{
    protected $action;
    
    public function __construct($controller)
    {
        $this->controller = $controller;
    }
    
    public function runRequestAction($action, $closure)
    {
        $this->action = $action;
        
        $callbacks = $this->buildActionCallbacks();
        $callbacks->setRunner($this->controller);
        $this->controller->setCallbacks($callbacks);
        
        $this->controller->callbacks()->run('action', $closure, $this->controller);
        
        if (!$this->controller->isPerformed()) {
            $this->controller->render();
        }
    }
    
    protected function actionMethodExists()
    {
        $methodExists = false;
        $ctrlrClass   = get_class($this->controller);
        $refl         = $this->controller->getReflection();
        
        if ($refl->hasMethod($this->action)) {
            $method = $refl->getMethod($this->action);
            if (
                $method->getDeclaringClass()->getName() == $ctrlrClass &&
                $method->isPublic()
            ) {
                $methodExists = true;
            }
        }
        
        return $methodExists;
    }
    
    protected function buildActionCallbacks()
    {
        # TODO: Check cache and get filters from there instead of re-building them.
        $callbacks = new Callbacks();
        $callbacks->defineCallbacks([
            'action' => [
                'terminator' => 'isPerformed'
            ]
        ]);
        $filters = $this->extractFilters();
        $filters = $this->convertFilters($filters);
        $callbacks->setCallbacks('action', $filters);
        return $callbacks;
    }
    
    # TODO: make sure that callbacks defined by parent classes are called first.
    protected function extractFilters()
    {
        $invoker = function($methodName) {
            return $this->$methodName();
        };
        $invoker = $invoker->bindTo($this->controller, $this->controller);
        $filters = [];
        
        foreach ($this->controller->getReflection()->getMethods() as $method) {
            $methodName = $method->getName();
            if (
                strpos($methodName, 'Filters') === strlen($methodName) - 7      &&
                strpos($method->getDeclaringClass()->getName(), 'Rails') !== 0
            ) {
                $filters = array_merge_recursive($filters, $invoker($methodName));
            }
        }
        
        foreach ($this->controller->getAppControllersMethod('filters') as $closure) {
            $filters = array_merge_recursive($filters, $closure());
        }
        
        $filters = $invoker('filters');
        
        return $filters;
    }
    
    protected function convertFilters(array $filters)
    {
        $converted = [];
        foreach ($filters as $kind => $callbacks) {
            $converted[$kind] = [];
            foreach ($callbacks as $callback => $options) {
                if (is_int($callback)) {
                    if (!is_string($options)) {
                        throw new Exception\InvalidArgumentException(
                            sprintf(
                                'Must pass string if ommiting options, %s passed',
                                gettype($options)
                            )
                        );
                    }
                    $converted[$kind][$options] = [];
                } else {
                    $callbackOptions = [];
                    
                    if (isset($options['only'])) {
                        $only = (array)$options['only'];
                        $callbackOptions['if'] = new ActionFilter(
                            ActionFilter::ONLY,
                            $this->action,
                            $only
                        );
                    } elseif (isset($options['except'])) {
                        $except = (array)$options['except'];
                        $callbackOptions['if'] = new ActionFilter(
                            ActionFilter::EXCEPT,
                            $this->action,
                            $except
                        );
                    }
                    $converted[$kind][$callback] = $callbackOptions;
                }
            }
        }
        return $converted;
    }
}
