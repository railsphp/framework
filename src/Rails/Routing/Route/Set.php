<?php
namespace Rails\Routing\Route;

use Rails\ActiveRecord\Base;
use Rails\Routing\Resources\Resources;
use Rails\Routing\ActionToken;
use Rails\Cache\Cache;

# TODO: Change name to RouteSet
class Set implements \ArrayAccess, \IteratorAggregate
{
    protected $routes = [];
    
    protected $resources;
    
    protected $requestRoute;
    
    protected $matcher;
    
    protected $basePath;
    
    /**
     * @var Rails\Cache\Cache
     */
    protected $cache;
    
    public function __construct()
    {
        $this->matcher = new Matcher();
    }
    
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
    }
    
    public function setCache(Cache $cache)
    {
        $this->cache = $cache;
    }
    
    public function getIterator()
    {
        return new \ArrayObject($this->routes);
    }
    
    public function offsetSet($offset, $value)
    {
        if (null === $offset) {
            $this->routes[] = $value;
        } else {
            $this->routes[$offset] = $value;
        }
    }
    
    public function offsetExists($offset)
    {
        return isset($this->routes[$offset]);
    }
    
    public function offsetUnset($offset)
    {
        unset($this->routes[$offset]);
    }
    
    public function offsetGet($offset)
    {
        return isset($this->routes[$offset]) ? $this->routes[$offset] : null;
    }
    
    public function routes()
    {
        return $this->routes;
    }
    
    public function setRequestRoute(BuiltRoute $route)
    {
        $this->requestRoute = $route;
    }
    
    public function requestRoute()
    {
        return $this->requestRoute;
    }
    
    public function resources()
    {
        if (!$this->resources) {
            $this->resources = new Resources($this);
        }
        return $this->resources;
    }
    
    /**
     * Match
     * Matches routes against the given URL path and HTTP verb.
     * If a route matches, returns an array containing the route and the
     * route parameters found in the given path.
     *
     * @return false|array
     */
    public function match($path, $verb)
    {
        foreach ($this->routes as $route) {
            $route->build();
            if (false !== ($routeParams = $this->matcher->match($route->build(), $path, $verb))) {
                return [$route, $routeParams];
            }
        }
        return false;
    }
    
    /**
     * Options used by this method:
     * * route - Returns both the built path and the matched route.
     */
    public function urlFor($criteria, array $options = [])
    {
        if (
            (is_string($criteria) && ($criteria = [$criteria])) ||
            is_array($criteria)
        ) {
            $token   = new ActionToken(array_shift($criteria));
            
            foreach ($this->routes as $route) {
                $route->build();
                
                if ($route->to() == $token->toString()) {
                    if ($path = $this->buildRouteUrl($route, $criteria, $options)) {
                        if (!empty($options['route'])) {
                            return [$path, $route];
                        }
                        return $path;
                    }
                }
            }
        }
        # TODO: invalid argument exception?
        return false;
    }
    
    public function urlForAlias($alias, array $vars = [], array $options = [])
    {
        foreach ($this->routes as $route) {
            $route->build();
            
            if ($route->name() == $alias) {
                if ($path = $this->buildRouteUrl($route, $vars, $options)) {
                    return $path;
                }
            }
        }
    }
    
    protected function buildRouteUrl($route, array $vars = [], array $options)
    {
        if (current($vars) instanceof Base) {
            $vars = $this->extractVarsFromModel($route, current($vars));
        }
        
        if ($path = $this->matcher->buildPath($route, $vars)) {
            if ($vars) {
                $path .= '?' . http_build_query($vars);
            }
            
            if (!isset($options['basePath']) || $options['basePath']) {
                return $this->basePath . $path;
            }
            return $path;
        }
    }
    
    protected function extractVarsFromModel($route, $model)
    {
        $varNames = array_keys($route->vars());
        $vars = [];
        
        foreach ($varNames as $varName) {
            if ($model->getAttributes()->isAttribute($varName)) {
                $vars[$varName] = $model->getAttribute($varName);
            }
        }
        
        return $vars;
    }
}
