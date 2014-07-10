<?php
namespace Rails\ActionDispatch;

use Rails\Routing\Route\BuiltRoute;

class Request
{
    protected $requestRoute;
    
    protected $format;
    
    protected $basePath;
    
    protected static $LOCALHOST = [
        '127.0.0.1',
        '::1'
    ];
    
    /**
     * List of methods allowed through the _method parameter
     * in a POST request.
     */
    protected static $allowedHackMethods = [
        'PUT',
        'PATCH',
        'DELETE',
    ];
    
    protected $isLocal;
    
    public function setRequestRoute(BuiltRoute $route)
    {
        $this->requestRoute = $route;
    }
    
    /**
     * Request path without the query string.
     * The application's basePath (i.e. if the app is ran under a subdirectory),
     * is cut off.
     * To get the complete path, call originalPath.
     *
     * @return string
     */
    public function path()
    {
        # TODO:
        return substr($this->originalPath(), strlen($this->basePath()));
    }
    
    /**
     * Request path without the query string.
     * The application's basePath is included.
     *
     * @return string
     */
    public function originalPath()
    {
        if (is_int($pos = strpos($this->getParam('REQUEST_URI'), '?'))) {
            return substr($this->getParam('REQUEST_URI'), 0, $pos);
        }
        return substr($this->getParam('REQUEST_URI'), 0);
    }
    
    /**
     * Full request path, includes query string, but excludes basePath.
     *
     * @return string
     */
    public function fullPath()
    {
        return substr($this->getParam('REQUEST_URI'), strlen($this->basePath()));
    }
    
    /**
     * Full request path, includes both basePath and query string.
     *
     * @return string
     */
    public function originalFullPath()
    {
        return $this->getParam('REQUEST_URI');
    }
    
    public function controller()
    {
        if ($this->requestRoute) {
            return $this->requestRoute->controller();
        };
    }
    
    public function action()
    {
        if ($this->requestRoute) {
            return $this->requestRoute->action();
        };
    }
    
    public function isGet()
    {
        return $this->method() === 'GET';
    }
    
    public function isPost()
    {
        return $this->method() == 'POST';
    }
    
    public function isPut()
    {
        return $this->method() == 'PUT';
    }
    
    public function isDelete()
    {
        return $this->method() == 'DELETE';
    }
    
    public function isPatch()
    {
        return $this->method() == 'PATCH';
    }
    
    /**
     * Checks the request method.
     */
    public function is($method)
    {
        $method = strtoupper($method);
        return $this->method() == $method;
    }
    
    public function setIsLocal($isLocal)
    {
        $this->isLocal = (bool)$isLocal;
    }
    
    public function isLocal()
    {
        # TODO: use above method to set if is local or not.
        return $this->isLocal ?: in_array($this->remoteIp(), self::$LOCALHOST);
    }
    
    public function remoteIp()
    {
        if ($this->getParam('HTTP_CLIENT_IP')) {
            $remoteIp = $this->getParam('HTTP_CLIENT_IP');
        } elseif ($this->getParam('HTTP_X_FORWARDED_FOR')) {
            $remoteIp = $this->getParam('HTTP_X_FORWARDED_FOR');
        } else {
            $remoteIp = $this->getParam('REMOTE_ADDR');
        }
        return $remoteIp;
    }
    
    /**
     * Returns the overridden method name.
     *
     * @return string
     */
    public function method()
    {
        if (isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
            if (in_array($method, self::$allowedHackMethods)) {
                return $method;
            }
        }
        return $this->getParam('REQUEST_METHOD');
    }
    
    public function protocol()
    {
        $protocol = ($val = $this->getParam('HTTPS')) && $val !== 'off' ? 'https' : 'http';
        return $protocol . '://';
    }
    
    public function isXmlHttpRequest()
    {
        return $this->getParam("HTTP_X_REQUESTED_WITH") === "XMLHttpRequest";
    }
    
    public function host()
    {
        return $this->getParam('server_name');
    }
    
    public function format()
    {
        # TODO: there's probably a better way to get the request format.
        if (!$this->format) {
            preg_match('/\.(\w+)$/', $this->path(), $m);
            
            if ($m) {
                $this->format = $m[1];
            } else {
                $this->format = 'html';
            }
        }
        return $this->format;
        // return 'html';
        // if ($this->requestRoute) {
            // return $this->requestRoute->format();
        // }
    }
    
    /**
     * Gets an index in the $_SERVER superglobal, or null
     * if not set.
     *
     * @return null|string
     */
    public function getParam($name)
    {
        $name = strtoupper($name);
        if (isset($_SERVER[$name])) {
            return $_SERVER[$name];
        }
        return null;
    }
    
    public function basePath()
    {
        if ($this->basePath === null) {
            $this->basePath = $this->detectBasePath();
        }
        return $this->basePath;
    }
    
    protected function detectBasePath()
    {
        # TODO: review this method. This may be moved somewhere else.
        $request = $this;
        
        $baseUrl        = '';
        $filename       = $request->getParam('SCRIPT_FILENAME');
        $scriptName     = $request->getParam('SCRIPT_NAME');
        $phpSelf        = $request->getParam('PHP_SELF');
        $origScriptName = $request->getParam('ORIG_SCRIPT_NAME');

        if ($scriptName !== null && basename($scriptName) === $filename) {
            $baseUrl = $scriptName;
        } elseif ($phpSelf !== null && basename($phpSelf) === $filename) {
            $baseUrl = $phpSelf;
        } elseif ($origScriptName !== null && basename($origScriptName) === $filename) {
            $baseUrl = $origScriptName;
        } else {
            $baseUrl  = '/';
            $basename = basename($filename);
            if ($basename) {
                $path     = ($phpSelf ? trim($phpSelf, '/') : '');
                $baseUrl .= substr($path, 0, strpos($path, $basename)) . $basename;
            }
        }

        $requestUri = $request->getParam('REQUEST_URI');

        if (0 === strpos($requestUri, $baseUrl)) {
            return $baseUrl;
        }

        $baseDir = str_replace('\\', '/', dirname($baseUrl));
        if (0 === strpos($requestUri, $baseDir)) {
            return $baseDir;
        }

        $truncatedRequestUri = $requestUri;

        if (false !== ($pos = strpos($requestUri, '?'))) {
            $truncatedRequestUri = substr($requestUri, 0, $pos);
        }

        $basename = basename($baseUrl);

        if (empty($basename) || false === strpos($truncatedRequestUri, $basename)) {
            return '';
        }
        
        if (strlen($requestUri) >= strlen($baseUrl)
            && (false !== ($pos = strpos($requestUri, $baseUrl)) && $pos !== 0)
        ) {
            $baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
        }

        return $baseUrl;
    }
}
