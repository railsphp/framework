<?php
namespace Rails\ActionController;

use Rails\ActionView\ActionView;

class Rendering
{
    protected $controller;
    
    public function __construct(Base $controller)
    {
        $this->controller = $controller;
    }
    
    public function renderResponse(array $options)
    {
        $type  = key($options);
        $value = current($options);
        
        $this->processRenderOptions($options);
        
        switch ($type) {
            case 'nothing':
                $this->controller->head(200);
                return ' ';
                break;
            
            case 'action':
                return $this->renderAction($value, $options);
                break;
            
            case 'template':
                return $this->renderTemplate($value, $options);
                break;
            
            case 'partial':
                return $this->renderPartial($value, $options);
                break;
            
            case 'json':
                return $this->renderJson($value, $options);
                break;
            
            case 'file':
                # TODO: find file, check if its extension can be handled.
                # if it can, handle it. otherwise, just file_get_contents() it.
                # then check if it should be wrapped in a layout.
                break;
        }
    }
    
    /**
     * Render action
     */
    public function renderAction($action, array $options)
    {
        return $this->renderTemplate(
            $this->controller->getService('inflector')->underscore($action),
            $options
        );
    }
    
    public function renderTemplate($template, array $options)
    {
        $this->generateRenderOptions($options);
        $this->setUpContext($options);
        $this->setAssigns($options);
        return $this->actionView()->renderTemplate($template, $options);
    }
    
    public function actionView()
    {
        return $this->controller->actionView();
    }
    
    public function renderJson($var, array $options)
    {
        if (is_string($var)) {
            $json = $var;
        } elseif (is_object($var)) {
            if ($var instanceof \Rails\ActiveRecord\Base) {
                $json = $var->toJson();
            } elseif ($var instanceof \Rails\ActiveModel\Collection) {
                $json = $var->toJson();
            } else {
                $json = json_encode($var);
            }
        } else {
            $json = json_encode($var);
        }
        
        $this->controller->response()->setContentType('application/json');
        return $json;
    }
    
    protected function setUpContext(array $options)
    {
        $context = $this->actionView()->lookupContext();
        $context->prefixes = $options['prefixes'];
        $context->handlers = $options['details']['handlers'];
        $context->locales  = $options['details']['locale'];
        $context->formats  = $options['details']['formats'];
    }
    
    protected function processRenderOptions(array &$renderOptions)
    {
        if (!empty($renderOptions['nothing'])) {
            return false;
        }
        
        if (isset($renderOptions['layout'])) {
            if (is_string($renderOptions['layout'])) {
                # If layout's a string, set the layout.
                $this->controller->setLayout($renderOptions['layout']);
            } elseif ($renderOptions['layout']) {
                # Assuming this is "true"
                $this->controller->setLayout(null);
            } else {
                # Assuming this is "false"
                $this->controller->setLayout(false);
            }
            unset($renderOptions);
        }
        return true;
    }
    
    /**
     * Set assigns for template
     * Checks if the 'assigns' value was passed as option, and
     * sets all the assigns set there to the Assigns object.
     * If the value for 'assigns' isn't an array, it is ignored.
     * Then the 'assigns' value is set to the Assigns object, which
     * will be used when rendering the template.
     *
     * @return void
     */
    protected function setAssigns(array &$options)
    {
        $assigns = $this->controller->assigns();
        if (isset($options['assigns']) && is_array($options['assigns'])) {
            foreach ($options['assigns'] as $name => $value) {
                $assigns->set($name, $value);
            }
        }
        $options['assigns'] = $assigns;
    }
    
    /**
     * Generate render options
     * Sets the default 'details' for template rendering.
     */
    protected function generateRenderOptions(array &$options)
    {
        $i18n = $this->controller->getService('i18n');
        $defaultLocales = $i18n->fallbacks();
        array_unshift($defaultLocales, $i18n->locale());
        
        $format = $this->controller->request() ?
            $this->controller->request()->format() :
            null;
        
        if (isset($options['details'])) {
            $details = $options['details'];
        } else {
            $details = [];
        }
        
        if (!isset($details['formats'])) {
            $details['formats'] = [];
        }
        if ($format) {
            $details['formats'][] = $format;
        }
        
        if (!isset($details['handlers'])) {
            $details['handlers'] = [];
        }
        $details['handlers'] = array_merge(
            array_keys($this->actionView()->handlers()),
            $details['handlers']
        );
        
        if (!isset($details['locale'])) {
            $details['locale'] = [];
        }
        $details['locale'] = array_merge($defaultLocales, $details['locale']);
        
        $options['details'] = $details;
        
        $layout = $this->controller->layout();
        if ($layout === null) {
            $options['layout'] = 'application';
        } elseif (is_string($layout)) {
            $options['layout'] = $layout;
        }
    }
}
