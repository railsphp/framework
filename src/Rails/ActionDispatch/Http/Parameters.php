<?php
namespace Rails\ActionDispatch\Http;

class Parameters implements \IteratorAggregate
{
    protected $deleteParams    = [];
    
    protected $putParams       = [];
    
    protected $patchParams     = [];
    
    protected $routeParams     = [];
    
    /**
     * Parameters for request methods other than
     * delete, put, post, get, patch.
     */
    protected $otherVerbParams = [];
    
    protected $jsonPayloadError = null;
    
    public function getIterator()
    {
        return new ArrayIterator($this->toArray());
    }
    
    public function __construct()
    {
        $method = !empty($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
        
        if (
            $method != 'GET' &&
            isset($_SERVER['CONTENT_TYPE']) &&
            strpos($_SERVER['CONTENT_TYPE'], 'application/json') === 0
        ) {
            $decoded = json_decode(file_get_contents('php://input'), true);
            
            if ($decoded === null) {
                $decoded = [];
                $this->jsonPayloadError = json_last_error();
            } else {
                if ($method == 'POST') {
                    $_POST = $decoded;
                } elseif ($method == 'DELETE') {
                    $this->deleteParams = $decoded;
                } elseif ($method == 'PUT') {
                    $this->putParams = $decoded;
                } elseif ($method == 'PATCH') {
                    $this->patchParams = $decoded;
                } else {
                    $this->otherVerbParams = $decoded;
                }
            }
        } elseif ($method != 'GET' && $method != 'POST') {
            parse_str(file_get_contents('php://input'), $decoded);
            
            if ($method == 'DELETE') {
                $this->deleteParams = $decoded;
            } elseif ($method == 'PUT') {
                $this->putParams = $decoded;
            } elseif ($method == 'PATCH') {
                $this->patchParams = $decoded;
            } else {
                $this->otherVerbParams = $decoded;
            }
        }
    }
    
    public function __get($prop)
    {
        return $this->getParam($prop);
    }
    
    public function __isset($prop)
    {
        return (bool)$this->getParam($prop);
    }
    
    /**
     * Looks for a parameter among many sources.
     * Note the order in which the parameters are returned:
     * Route, get, post, etc.
     * If the parameter isn't found, null is returned.
     *
     * @return mixed
     */
    public function getParam($prop)
    {
        if (isset($this->routeParams[$prop])) {
            return $this->routeParams[$prop];
        }
        
        $var = $this->searchParam($prop);
        
        if ($var) {
            global ${$var};
            return ${$var}[$prop];
        } else {
            if (isset($this->putParams[$prop])) {
                return $this->putParams[$prop];
            } elseif (isset($this->deleteParams[$prop])) {
                return $this->deleteParams[$prop];
            } elseif (isset($this->patchParams[$prop])) {
                return $this->patchParams[$prop];
            } elseif (isset($this->otherVerbParams[$prop])) {
                return $this->otherVerbParams[$prop];
            }
        }
        
        return null;
    }
    
    public function setRouteVars(array $vars)
    {
        $this->routeParams = array_filter($vars, function($x) {
            /**
             * Filter empty strings.
             */
            return $x !== '';
        });
    }
    
    public function get()
    {
        return $_GET;
    }
    
    public function post()
    {
        return $_POST;
    }
    
    public function delete()
    {
        return $this->deleteParams;
    }
    
    public function put()
    {
        return $this->putParams;
    }
    
    public function patch()
    {
        return $this->patchParams;
    }
    
    public function route()
    {
        return $this->routeParams;
    }
    
    public function others()
    {
        return $this->otherVerbParams;
    }
    
    public function toArray()
    {
        return array_merge(
            $this->delete(),
            $this->put(),
            $this->patch(),
            $this->others(),
            $this->post(),
            $this->get(),
            $this->routeParams
        );
    }
    
    /**
     * Alias of toArray().
     */
    public function all()
    {
        return $this->toArray();
    }
    
    public function jsonPayloadError()
    {
        return $this->jsonPayloadError;
    }
    
    protected function searchParam($index)
    {
        if (isset($_GET[$index])) {
            return '_GET';
        } elseif (isset($_POST[$index])) {
            return '_POST';
        } else {
            return false;
        }
    }
}
