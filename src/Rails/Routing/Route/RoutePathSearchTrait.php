<?php
namespace Rails\Routing\Route;

use Rails\Routing\ActionToken;

trait RoutePathSearchTrait
{
    public function urlFor($criteria, array $options = [])
    {
        if (is_string($criteria)) {
            if ($criteria == 'root') {
                if ($this->routeSet->requestRoute()) {
                    $namespaces = implode('/', $this->routeSet->requestRoute()->to()->namespaces());
                    if ($namespaces) {
                        $criteria = $namespaces . '/root';
                    }
                }
                return $this->routeSet->pathFor($criteria);
            } elseif ($criteria == '/root') {
                return $this->routeSet->pathFor('root');
            } elseif (
                strpos($criteria, '/')    === 0 ||
                strpos($criteria, 'http') === 0
            ) {
                return $criteria;
            } elseif (strpos($criteria, ActionToken::METHOD_SEPARATOR) === false) {
                return $this->routeSet->pathFor($criteria);
            }
        }
        return $this->routeSet->urlFor($criteria, $options);
    }
}
