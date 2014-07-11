<?php
namespace Rails\ActionView;

use ArrayObject;
use Zend\ServiceManager\Exception\ExceptionInterface as ZfSmEInterface;
use Rails\Cache\Cache;
use Rails\ActionView\Renderer\Exception as RendererException;
use Rails\ActionView\Renderer\Renderer;
use Rails\ActionView\Helper\HelperSet;
use Rails\ActionView\Template\Assigns;
use Rails\Routing\Route\RouteSet;

class ActionView
{
    use \Rails\ServiceManager\ServiceLocatorAwareTrait;
    
    protected $finder;
    
    protected $lookupContext;
    
    protected $handlers = [
        'php'  => 'Rails\ActionView\Renderer\PhpRenderer',
        'jade' => 'Rails\ActionView\Renderer\JadeRenderer'
    ];
    
    protected $renderer;
    
    protected $cache;
    
    protected $helperSet;
    
    // protected $routeSet;
    
    public function __construct()
    {
        $this->lookupContext = new LookupContext();
        $this->lookupContext->formats = ['html', 'text', 'js', 'css',  'xml', 'json'];
    }
    
    public function lookupContext()
    {
        return $this->lookupContext;
    }
    
    // public function setRouteSet(RouteSet $routeSet)
    // {
        // $this->routeSet = $routeSet;
    // }
    
    public function setHelperSet(HelperSet $helperSet)
    {
        $this->helperSet = $helperSet;
    }
    
    public function helperSet()
    {
        if (!$this->helperSet) {
            $this->helperSet = new HelperSet();
        }
        return $this->helperSet;
    }
    
    public function setCache(Cache $cache)
    {
        $this->cache = $cache;
    }
    
    public function cache()
    {
        return $this->cache;
    }
    
    public function addHandler($type, $handler)
    {
        $this->handlers[$type] = $handler;
    }
    
    public function handlers()
    {
        return $this->handlers;
    }
    
    public function getHandler($extension)
    {
        $serviceName = 'actionView.handlers.' . $extension;
        
        if (isset($this->handlers[$extension])) {
            $handlerClass = $this->handlers[$extension];
            $handler = new $handlerClass();
        } else {
            return false;
        }
        
        return $handler;
    }
    
    public function renderer()
    {
        if (!$this->renderer) {
            $this->renderer = new Renderer($this);
        }
        return $this->renderer;
    }
    
    public function finder()
    {
        if (!$this->finder) {
            $this->finder = new Template\Finder($this);
        }
        return $this->finder;
    }
    
    public function renderWithPrefixes($templateName, array $prefixes, array $options)
    {
        $templates = array_map(function($prefix) {
            return $prefix . '/' . $templateName;
        }, $prefixes);
        
        $template = $this->finder()->findTemplate(
            $templateName,
            $options['partial'],
            $options['prefixes'],
            $options['details']['locale'],
            $options['details']['formats'],
            $options['details']['handlers']
        );
    }
    
    public function renderTemplate($templateName, array $options)
    {
        $template = $this->finder()->findTemplate(
            $templateName,
            $options['partial'],
            $options['prefixes'],
            $options['details']['locale'],
            $options['details']['formats'],
            $options['details']['handlers']
        );
        
        if (!$template) {
            throw new RendererException\TemplateMissingException(
                $this->generateMissingExceptionMessage(
                    $options['partial'] ? 'partial' : 'template',
                    $templateName,
                    $options['prefixes'],
                    $options['details']['locale'],
                    $options['details']['formats'],
                    $options['details']['handlers']
                )
            );
        }
        
        $this->fillOptions($options);
        return $this->renderer()->renderTemplate(
            $template,
            $options['layout'],
            $options['assigns'],
            $options['helperSet'],
            $options['captures']
        );
        
        // if ($template) {
            // $layout   = $options['layout'];
            // $assigns  = isset($options['assigns']) ? $options['assigns'] : new Assigns();
            // $contents = $this->renderer()->renderTemplate($template, $layout, $assigns, $this->helperSet());
            // return $contents;
        // } else {

        // }
    }
    
    public function renderPartial(
        $partialName,
        array $options,
        array $prefixes,
        array $locales,
        array $formats,
        array $handlers
    ) {
        $partial = $this->finder()->findTemplate(
            $partialName,
            true,
            $prefixes,
            $locales,
            $formats,
            $handlers
        );
        
        if (!$partial) {
            throw new RendererException\PartialMissingException(
                $this->generateMissingExceptionMessage(
                    'partial',
                    $partialName,
                    $prefixes,
                    $locales,
                    $formats,
                    $handlers
                )
            );
        }
        
        $this->fillOptions($options);
        return $this->renderer()->renderTemplate(
            $partial,
            $options['layout'],
            $options['assigns'],
            $options['helperSet'],
            $options['captures']
        );
    }
    
    protected function fillOptions(array &$options)
    {
        if (empty($options['layout'])) {
            $options['layout'] = false;
        }
        if (empty($options['assigns'])) {
            $options['assigns'] = new Assigns();
        }
        if (empty($options['helperSet'])) {
            $options['helperSet'] = $this->helperSet();
        }
        if (empty($options['captures'])) {
            $options['captures'] = new ArrayObject();
        }
    }
    
    // protected function renderObject($type, array $options = [])
    // {
        // if (empty($options['layout'])) {
            // $layout = false;
        // } else {
            // $layout = $options['layout'];
        // }
        
        // $assigns  = isset($options['assigns']) ? $options['assigns'] : new Assigns();
        // $contents = $this->renderer()->render($type, $template, $layout, $assigns, $this->helperSet());
        // return $contents;
    // }
    
    /**
     * Generate missing exception message
     *
     * @return string
     */
    protected function generateMissingExceptionMessage(
        $type,
        $name,
        array $prefixes,
        array $locales,
        array $formats,
        array $handlers
    ) {
        $searchedPaths = [];
        
        if ($prefixes) {
            foreach ($prefixes as $prefix) {
                $searchedPaths[] = $prefix . '/' . $name;
            }
        } else {
            $searchedPaths[] = $name;
        }
        
        if ($this->lookupContext->paths) {
            $lookupPaths =  sprintf("* %s", implode("\n * ", $this->lookupContext->paths));
        } else {
            $lookupPaths = '[no lookup paths]';
        }
        
        return sprintf(
            "Missing %s '%s' with [locales=>[%s], formats=>[%s], handlers=>[%s]], searched in:\n%s",
            $type,
            implode("', '", $searchedPaths),
            implode(', ', $locales),
            implode(', ', $formats),
            implode(', ', $handlers),
            $lookupPaths
        );
    }
}
