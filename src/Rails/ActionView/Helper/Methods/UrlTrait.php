<?php
namespace Rails\ActionView\Helper\Methods;

use Closure;
use Rails\Routing\Route\RoutePathSearchTrait;

trait UrlTrait
{
    use RoutePathSearchTrait;
    
    /**
         $this->linkTo('Edit', ['#edit', 'id' => $post->id], ['class' => 'edit-btn']);
         #-> <a href="..." class="edit-btn">Edit</a>
     */
    public function linkTo($text, $urlOptions, array $attrs = [])
    {
        $url = $this->parseUrlOptions($urlOptions);
        return $this->contentTag('a', $text, array_merge(['href' => $url], $attrs));
    }
    
    public function linkToIf($condition, $text, $urlOptions, array $attrs = [], $fallback = null)
    {
        if ($condition) {
            return $this->linkTo($text, $url_params, $attrs);
        } elseif ($fallback) {
            if ($fallback instanceof Closure) {
                return $fallback($text, $urlOptions, $attrs);
            } else {
                return $fallback;
            }
        } else {
            return $text;
        }
    }
    
    public function linkToUnlessCurrent($text, $urlOptions, array $attrs = [], $fallback = null)
    {
        return $this->linkToIf(!$this->currentPage($urlOptions), $text, $urlOptions, $attrs, $fallback);
    }
    
    // /**
     // * @var string|array $params
     // */
    // public function urlFor($params, array $options = [])
    // {
        // return $this->routeSet->urlFor($params, $options);
    // }
    
    // /**
     // * @var string|array $params
     // */
    // public function pathFor($routeName, array $vars = [], array $options = [])
    // {
        // return $this->routeSet->pathFor($routeName, $vars, $options);
    // }
    
    public function isCurrentPage($options)
    {
        $url = $this->parseUrlOptions($options, ['route' => true]);
        
        if (!$url) {
            return false;
        }
        
        if (is_array($url)) {
            list ($url, $route) = $url;
            
            if (!in_array(strtolower($this->request()->method()), $route->via())) {
                return false;
            }
        }
        
        if (is_int(strpos($url, '?'))) {
            if ($this->stripQuery($url) == $this->request()->originalPath()) {
                $urlParts = parse_url($url);
                if (isset($urlParts['query'])) {
                    parse_str($urlParts['query'], $urlQuery);
                } else {
                    $urlQuery = [];
                }
                
                $currentParts = parse_url($this->request()->fullPath());
                if (isset($currentParts['query'])) {
                    parse_str($currentParts['query'], $currentQuery);
                } else {
                    $currentQuery = [];
                }
                
                return $urlQuery == $currentQuery;
            } else {
                return false;
            }
        } else {
            return $url == $this->request()->originalPath();
        }
    }
    
    public function isUrl($var)
    {
        return
            is_string($var) && (
                strpos($var, '/')      === 0 ||
                strpos($var, 'http:')  === 0 ||
                strpos($var, 'https:') === 0
            );
    }
    
    protected function stripQuery($url)
    {
        if (is_int($pos = strpos($url, '?'))) {
            return substr($url, 0, $pos);
        } else {
            return $url;
        }
    }
    
    protected function parseUrlOptions($urlParams, array $options = [])
    {
        if (
            $urlParams != '#' &&
            (
                is_array($urlParams) ||
                (
                    is_string($urlParams) &&
                    (
                        strpos($urlParams, 'http') !== 0 &&
                        strpos($urlParams, '/')    !== 0
                    )
                )
            )
        ) {
            // if (!is_array($urlParams)) {
                // $urlParams = [$urlParams];
            // }
            
            return $this->urlFor($urlParams, $options);
        } else {
            return $urlParams;
        }
    }
}
