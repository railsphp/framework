<?php
namespace Rails\Routing;

class ActionToken
{
    const METHOD_SEPARATOR    = '#';
    
    // const NAMESPACE_SEPARATOR = '\\';
    const NAMESPACE_SEPARATOR = '.';
    
    /**
     * Set a route with which tokens containing only the
     * action, like "#create", can be automatically completed
     * with this route's controller.
     *
     * @var object Route
     */
    protected static $route;
    
    protected $controller;
    
    protected $action     = 'index';
    
    protected $namespaces = [];
    
    public static function setRoute(Route\BuiltRoute $route)
    {
        self::$route = $route;
    }
    
    public static function fromArray($data)
    {
        $token = new self();
        $token->controller = $data['controller'];
        $token->action     = $data['action'];
        $token->namespaces = $data['namespaces'];
        return $token;
    }
    
    public function __construct($param = null)
    {
        if ($param) {
            if (is_string($param)) {
                $this->parseToken($param);
            } else {
                $this->parseParts($param);
            }
        }
    }
    
    public function __toString()
    {
        return $this->toString();
    }
    
    public function parts()
    {
        return array($this->controller, $this->action, $this->namespaces);
    }
    
    public function controller()
    {
        return $this->controller;
    }
    
    public function action()
    {
        return $this->action;
    }
    
    public function namespaces()
    {
        return $this->namespaces;
    }
    
    /**
     * Returns the token string, like 'posts#index'.
     */
    public function toString()
    {
        $namespace = $this->namespaces ?
                        implode(self::NAMESPACE_SEPARATOR, $this->namespaces) 
                            . self::NAMESPACE_SEPARATOR
                        : '';
        return $namespace . $this->controller . self::METHOD_SEPARATOR . $this->action;
    }
    
    public function toPath()
    {
        return str_replace([self::METHOD_SEPARATOR, self::NAMESPACE_SEPARATOR], '/', $this->toString());
    }
    
    public function toClass()
    {
        $namespaces = [];
        
        if ($this->namespaces) {
            foreach ($this->namespaces as $ns) {
                $namespaces[] = ucfirst($ns);
            }
        }
        
        return
            ($namespaces ? implode('\\', $namespaces) . '\\' : '') .
            ucfirst($this->controller);
    }
    
    public function toJson()
    {
        return json_encode([
            'controller' => $this->controller,
            'action'     => $this->action,
            'namespaces' => $this->namespaces,
        ]);
    }
    
    public function toArray()
    {
        return [
            'controller' => $this->controller,
            'action'     => $this->action,
            'namespaces' => $this->namespaces,
        ];
    }
    
    protected function parseParts(array $parts)
    {
        if (empty($parts['controller'])) {
            list ($this->controller, $this->namespaces) = $this->getCurrentController();
        } else {
            $this->controller = $parts['controller'];
            if (!empty($parts['namespaces'])) {
                if (is_array($parts['namespaces'])) {
                    $this->namespaces =
                        $this->normalizeNamespaces(
                            implode(self::NAMESPACE_SEPARATOR, $parts['namespaces'])
                        );
                } else {
                    $this->namespaces = $this->normalizeNamespaces($parts['namespaces']);
                }
            }
        }
        
        if (isset($parts['action'])) {
            $this->action = $parts['action'];
        }
    }
    
    protected function normalizeNamespaces($namespaces)
    {
        return array_filter(
            explode(
                self::NAMESPACE_SEPARATOR,
                $namespaces
            )
        );
    }
    
    protected function parseToken($token)
    {
        if (is_int($pos = strpos($token, self::NAMESPACE_SEPARATOR))) {
            if (!$pos) {
                $token = substr($token, 1);
            } else {
                $namespace = substr($token, 0, $pos);
                $token     = substr($token, $pos + 1);
                $this->namespaces = $this->normalizeNamespaces($namespace);
            }
        }
        
        if (is_bool(strpos($token, self::METHOD_SEPARATOR))) {
            throw new Exception\InvalidArgumentException(
                sprintf("Missing METHOD_SEPARATOR in token '%s'", $token)
            );
        }
        
        $parts = explode(self::METHOD_SEPARATOR, $token);
        
        if (empty($parts[0])) {
            list ($this->controller, $this->namespaces) = $this->getCurrentController();
        } else {
            $this->controller = $parts[0];
        }
        
        if (!empty($parts[1])) {
            $this->action = $parts[1];
        }
    }
    
    protected function getCurrentController()
    {
        if (!self::$route) {
            throw new Exception\RuntimeException(
                "Can't complete path token as there's no route set"
            );
        }
        return [self::$route->controller(), self::$route->namespaces()];
    }
}
