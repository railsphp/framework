<?php
namespace Rails\ActionView\Renderer;

use Closure;
use ArrayObject;
use Rails\ServiceManager\ServiceLocatorAwareTrait;
use Rails\ActiveModel\Collection as ModelCollection;
use Rails\ActiveModel\Base as ModelBase;
use Rails\ActionView\ActionView;
use Rails\ActionView\Renderer\Renderer;
use Rails\ActionView\Renderer\TemplateRenderer;
use Rails\ActionView\Template\Assigns;
use Rails\ActionView\Template\Template;
use Rails\ActionView\Helper\HelperSet;

abstract class Base
{
    use ServiceLocatorAwareTrait;
    
    const CONTENT_CAPTURE_INDEX = 'LAYOUT.CONTENTS';
    
    protected static $PARTIAL_OPTIONS = [
        'layout'     => null,
        'object'     => null,
        'as'         => null,
        'collection' => null,
        'locale'     => null,
        'handlers'   => null,
        'formats'    => null,
    ];
    
    protected $assigns;
    
    protected $helperSet;
    
    protected $template;
    
    protected $captures;
    
    protected $actionView;
    
    abstract public function processRender();
    
    public function renderTemplate(
        $template,
        $layout,
        Assigns     $assigns,
        ArrayObject $captures,
        HelperSet   $helperSet,
        ActionView  $actionView
    ) {
        $this->template  = $template;
        $this->assigns   = $assigns;
        $this->captures  = $captures;
        $this->helperSet = $helperSet;
        $this->actionView = $actionView;
        
        $contents = $this->processRender();
        
        if ($layout) {
            $this->wrapInLayout($contents, $layout);
        }
        
        return $contents;
    }
    
    public function __call($method, $args)
    {
        if ($helper = $this->helperSet->findHelper($method)) {
            return call_user_func_array([$helper, $method], $args);
        }
        
        throw new Exception\BadMethodCallException(
            sprintf(
                "Called to unknown method/helper '%s'",
                $method
            )
        );
    }
    
    public function __get($prop)
    {
        return $this->assigns->get($prop);
    }
    
    public function __set($prop, $value)
    {
        return $this->assigns->set($prop, $value);
    }
    
    public function setHelpers($helpers)
    {
        $this->helpers = $helpers;
    }
    
    public function setAssigns(Assigns $assigns)
    {
        $this->assigns = $assigns;
    }
    
    /**
     * Returns the contents for layouts.
     */
    public function contents()
    {
        return $this->contentFor(self::CONTENT_CAPTURE_INDEX);
    }
    
    public function render(/* ...! */)
    {
        # TODO
    }
    
    public function modelPartial(ModelBase $model)
    {
        
    }
    
    /**
     * Render partial.
     * For example, we are rendering `views/posts/index.php`, which calls a partial in
     * a different directory:
     * `$this->partial('users/profile')`
     * which would render `views/users/_profile.php`, and inside this partial we render
     * another partial like this:
     * `$this->partial('name')`
     * If nothing but the partial name is specified, the path to look into becomes
     * relative to the controller's path, which would be `posts` in this case (`views/posts/_name.php`).
     * If this partial fails to be found,
     * the next directory to look into would normally be the `application` folder
     * (`views/application/_name.php`).
     *
     * If a slash is specified, the directory becomes relative to the view root path. For example,
     * in `views/users/_profile.php` we have this:
     * `$this->partial('comments/preview')`
     * That would render the file at `views/comments/_preview.php`. In order to make the path
     * relative to the active template's path, add a dot like this:
     * `$this->partial('./comments/preview')`
     * That would render `views/users/comments/_preview.php`.
     */
    public function partial($partialName, array $options = [])
    {
        if (isset($options['collection'])) {
            $collection = $options['collection'];
            unset($options['collection']);
            return $this->partialCollection($collection, $partialName, $options);
        }
        
        $this->normalizePartialOptions($options);
        $this->definePartialObject($partialName, $options);
        
        if (strpos($partialName, '.') === 0) {
            # It is assumed the dot is followed by a slash.
            list($name, $prefixes) = $this->separatePrefixFromName(substr($partialName, 2));
        } elseif (is_int(strpos($partialName, '/'))) {
            $name     = $partialName;
            $prefixes = [];
        } else {
            $prefixes = $this->actionView->lookupContext()->prefixes;
            $name     = $partialName;
        }
        
        list($locales, $handlers, $formats) = $this->normalizeRenderOptions($options);
        
        return $this->actionView->renderPartial(
            $name,
            $options,
            $prefixes,
            $locales,
            $formats,
            $handlers
        );
    }
    
    public function partialCollection(ModelCollection $collection, $partialName, array $options = [])
    {
        if (!$collection->any()) {
            return '';
        }
        
        if (isset($options['spacerTemplate'])) {
            $spacerTemplate = $options['spacerTemplate'];
            unset($options['spacerTemplate']);
        } else {
            $spacerTemplate = null;
        }
        
        $contents = '';
        
        foreach ($collection as $member) {
            $options['object'] = $member;
            $contents .= $this->partial($partialName, $options);
            
            if ($spacerTemplate) {
                $contents .= $this->partial($spacerTemplate);
            }
        }
        
        return $contents;
    }
    
    protected function definePartialObject($partialName, array &$options)
    {
        # If 'object' wasn't passed, check if an assign with the name of
        # the partial exists, and set that value as 'object'.
        if (!isset($options['object'])) {
            if ($this->assigns->exists($partialName)) {
                $options['object'] = $this->assigns->get($partialName);
            }
        }
        # If 'object' exists and the 'as' option also exists, set the
        # object under the name defined by 'as' in the assigns. Otherwise,
        # set it under the name of 'object'.
        if (isset($options['object'])) {
            if (isset($options['as'])) {
                $options['assigns']['as'] = $options['object'];
            } else {
                $options['assigns']['object'] = $options['object'];
            }
            unset($options['object'], $options['as']);
        }
    }
    
    protected function wrapInLayout(&$contents, $layout)
    {
        list($locale, $handlers, $formats) = $this->normalizeRenderOptions([]);
        
        if (is_string($layout)) {
            $layout = $this->template->finder()->findTemplate(
                $layout,
                false,
                # TODO: hardcoded prefixes 
                ['layouts', '.'],
                $locale,
                $formats,
                $handlers
            );
        } elseif (!$layout instanceof Template) {
            throw new \Exception(
                "Layout must be either string or instance of Template"
            );
        }
        
        if ($layout) {
            $this->captures[self::CONTENT_CAPTURE_INDEX] = $contents;
            $contents = $this->actionView->renderer()->renderTemplate($layout, false, $this->assigns, $this->helperSet, $this->captures);
            unset($this->captures[self::CONTENT_CAPTURE_INDEX]);
        }
    }
    
    protected function separatePrefixFromName($partialName)
    {
        $lastPos = strrpos($partialName, '/');
        $prefix  = substr($partialName, 0, $lastPos);
        
        if (!$prefix) {
            $prefix = $this->template->prefix();
        } else {
            $prefix = $this->template->prefix() . '/' . $prefix;
        }
        
        return [
            $lastPos ? substr($partialName, $lastPos + 1) : $partialName,
            [$prefix]
        ];
    }
    
    protected function normalizeRenderOptions($options)
    {
        foreach (['locale', 'formats', 'handlers'] as $key) {
            if (!isset($options[$key])) {
                if ($key == 'locale') {
                    $$key = $this->actionView->lookupContext()->locales;
                } else {
                    $$key = $this->actionView->lookupContext()->$key;
                }
            } else {
                $$key = (array)$options[$key];
            }
            
            if ($key == 'locale') {
                if ($this->template->locale()) {
                    array_unshift($$key, $this->template->locale());
                }
            } else {
                if ($this->template->{substr($key, 0, -1)}()) {
                    array_unshift($$key, $this->template->{substr($key, 0, -1)}());
                }
            }
            $$key = array_unique($$key);
        }
        
        return [$locale, $handlers, $formats];
    }
    
    protected function normalizePartialOptions(array &$options)
    {
        if (!$options) {
            // return [[], []];
            $options['assigns'] = [];
        } elseif (isset($options['locals'])) {
            $options['assigns'] = $options['locals'];
            unset($options['locals']);
            // return [$options, $locals];
        } else {
            $optionsCopy = $options;
            $options = array_intersect_key($optionsCopy, self::$PARTIAL_OPTIONS);
            $options['assigns'] = array_diff_key($optionsCopy, self::$PARTIAL_OPTIONS);
            // return [$options, $locals];
        }
    }
    
    /** Capture { **/
    public function captures()
    {
        return $this->captures;
    }
    
    public function contentFor($name)
    {
        if ($this->contentForExists($name)) {
            return $this->captures[$name];
        }
        return '';
    }
    
    public function setContentFor($name, $options = null, $content = null)
    {
        if (!is_array($options)) {
            $content = $options;
            $options = [];
        }
        
        if (!isset($this->captures[$name]) || !empty($options['flush'])) {
            $this->captures[$name] = '';
        }
        
        if ($content instanceof Closure) {
            ob_start();
            $content();
            $content = ob_get_clean();
        } elseif (!is_string($content)) {
            $content = (string)$content;
        }
        
        if (!empty($options['prepend'])) {
            $this->captures[$name] = $content . $this->captures[$name];
        } else {
            $this->captures[$name] .= $content;
        }
    }
    
    public function provide($name, $content)
    {
        $this->setContentFor($name, $content);
    }
    
    public function contentForExists($name)
    {
        return array_key_exists($name, $this->captures);
    }
    /** } Capture **/
}
