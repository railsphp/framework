<?php
namespace Rails\ActionDispatch\Http;

use Negotiation\FormatNegotiator;
use Rails\Routing\Route\BuiltRoute;

class Request
{
    protected $requestRoute;
    
    /**
     * @var string
     */
    protected $format;
    
    protected $basePath;
    
    /**
     * @var Parameters
     */
    protected $parameters;
    
    protected $files;
    
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
    
    public function __construct(Parameters $parameters)
    {
        $this->parameters = $parameters;
    }
    
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
        if (isset($this->parameters->post()['_method'])) {
            $method = strtoupper($this->parameters->post()['_method']);
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
    
    /**
     * Looks for the best format that fits the request.
     * If format is not specified in the request parameters, FormatNegotiator
     * is used to determine the best format.
     * If everything fails, html is assumed.
     *
     * @return string
     */
    public function format()
    {
        if ($this->format === null) {
            if (null !== ($format = $this->parameters->getParam('format'))) {
                $this->format = $format;
            } else {
                $headers = getallheaders();
                if (isset($headers['Accept'])) {
                    $acceptHeader = $headers['Accept'];
                    
                    $negotiator = new FormatNegotiator();
                    $priorities = ['html', 'json', 'xml', 'js', '*.*'];
                    $this->format = $negotiator->getBestFormat($acceptHeader, $priorities) ?: 'html';
                } else {
                    $this->format = 'html';
                }
            }
        }
        return $this->format;
    }
    
    /**
     * Gets an index in the $_SERVER superglobal, or null if not set.
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
    
    /**
     * Get uploaded files.
     *
     * @return array
     */
    public function uploadedFiles()
    {
        if (!$this->files) {
            $this->files = new UploadedFiles($_FILES);
        }
        return $this->files->files();
    }
    
    /**
     * Get a single uploaded file. Use this if you expect the $keys
     * to contain a single file. If the file key is found but it's not
     * a single file, null is returned.
     *
     * ```php
     * // <input type="file" name="post[image]" />
     * $request->getUploadedFile('post', 'image');
     * ```
     *
     * @return UploadedFile|null
     */
    public function getUploadedFile(/*...$keys*/)
    {
        $file = $this->mapKeysToUploadedFiles(func_get_args());
        if ($file instanceof UploadedFile) {
            return $file;
        }
        return null;
    }
    
    /**
     * Get an array of uploaded files. Use this if you expect the $keys
     * to contain multiple files. If the file key is found but it's not
     * an array, null is returned.
     *
     * ```php
     * // <input type="file" name="post[images][]" multiple />
     * $request->getUploadedFiles('post', 'images');
     * ```
     *
     * @return UploadedFile[]|null
     */
    public function getUploadedFiles(/*...$keys*/)
    {
        $files = $this->mapKeysToUploadedFiles(func_get_args());
        if (is_array($files)) {
            return $files;
        }
        return null;
    }
    
    protected function mapKeysToUploadedFiles(array $keys)
    {
        $current = $this->uploadedFiles();
        foreach ($keys as $key) {
            if (isset($current[$key])) {
                $current = $current[$key];
            } else {
                return null;
            }
        }
        return $current;
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
