<?php
namespace Rails\ActionView\Helper;

use Rails\ActionView\Helper as AbstractHelper;
use Rails\Routing\Route\RouteSet;

class BaseHelper extends AbstractHelper
{
    use Methods\AssetTagTrait, Methods\AssetUrlTrait, Methods\TagTrait,
        Methods\SanitizeTrait, Methods\PresenterTrait,
        Methods\InflectorTrait, Methods\UrlTrait,
        Methods\FormTrait, Methods\FormTagTrait, Methods\TranslationTrait,
        Methods\FormOptionsTrait;
    
    protected $routeSet;
    
    protected $config;
    
    public function __construct(HelperSet $helperSet)
    {
        parent::__construct($helperSet);
        $this->config = $this->getService('rails.config');
    }
    
    public function setRouteSet(RouteSet $routeSet)
    {
        $this->routeSet = $routeSet;
    }
    
    public function routeSet()
    {
        return $this->routeSet;
    }
    
    public function getAssign($name)
    {
        return $this->helperSet->assigns()->get($name);
    }
}
