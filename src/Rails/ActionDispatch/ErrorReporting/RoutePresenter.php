<?php
namespace Rails\ActionDispatch\ErrorReporting;

use Rails\ActionDispatch\Dispatcher;

class RoutePresenter extends \Rails\ActionView\Presenter
{
    public function via()
    {
        return strtoupper(implode(', ', $this->object->via()));
    }
    
    public function pathRegex()
    {
        return trim($this->object->pathRegex(), '/');
    }
    
    public function endPoint()
    {
        $endPoint = $this->object->endPoint();
        if (is_string($endPoint)) {
            return $endPoint;
        } elseif ($endPoint instanceof Dispatcher) {
            return $this->object->to()->toString();
        } elseif ($endPoint instanceof SuperClosure) {
            return '[Closure]';
        } else {
            return 'Uknown EndPoint: ' . gettype($endPoint);
        }
    }
}
