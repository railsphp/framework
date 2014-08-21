<?php
namespace Rails\Routing\Route;

trait RoutePathSearchTrait
{
    public function urlFor($criteria, array $options = [])
    {
        return $this->routeSet->urlFor($criteria, $options);
    }
    
    public function pathFor($name, $vars = [], array $options = [])
    {
        return $this->routeSet->pathFor($name, $vars, $options);
    }
}
