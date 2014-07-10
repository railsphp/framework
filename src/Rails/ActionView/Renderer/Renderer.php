<?php
namespace Rails\ActionView\Renderer;

use Rails\ActionView\ActionView;
use Rails\ActionView\Template\Template;

class Renderer
{
    protected $actionView;
    
    public function __construct(ActionView $actionView)
    {
        $this->actionView = $actionView;
    }
    
    public function actionView()
    {
        return $this->actionView;
    }
    
    public function render($type/*, ...$params*/)
    {
        $params = func_get_args();
        array_shift($params);
        
        switch ($type) {
            case 'template':
                return call_user_func_array([$this, 'renderTemplate'], $params);
            
            case 'partial':
                return call_user_func_array([$this, 'renderPartial'], $params);
            
            default:
                throw new Exception\InvalidArgumentException(
                    sprintf(
                        "Unknown render type '%s'",
                        $type
                    )
                );
        }
    }
    
    public function renderTemplate(Template $template, $layout, $assigns, $helperSet, $captures)
    {
        $handler = $this->actionView->getHandler($template->handler());
        
        if (!$handler) {
            throw new Exception\RuntimeException(
                sprintf("Handler for '%s' not defined", $template->handler())
            );
        }
        
        return $handler->renderTemplate($template, $layout, $assigns, $captures, $helperSet, $this->actionView);
    }
}
