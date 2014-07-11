<?php
namespace Rails\ActionController;

use ReflectionClass;
use Rails\ActionDispatch\Request;
use Rails\ActionDispatch\Http\Response;
use Rails\ActionDispatch\Http\Parameters;
use Rails\ActionDispatch\Http\Session;
use Rails\ActionDispatch\Cookies\CookieJar;
use Rails\ActionView\ActionView;
use Rails\ActionView\Helper\HelperSet;
use Rails\ActionView\Template\Assigns;
use Rails\ServiceManager\ServiceLocatorAwareTrait;
use Rails\Routing\Route\RouteSet;

/**
 *
 */
abstract class Base
{
    # TODO
    // use NamedPathAwareTrait;
    
    use ServiceLocatorAwareTrait;
    
    const APP_CONTROLLER_CLASS = 'ApplicationController';
    
    protected static $renderers = [
        
    ];
    
    protected $layout;
    
    protected $response;
    
    protected $request;
    
    protected $session;
    
    protected $params;
    
    protected $routeSet;
    
    protected $assigns;
    
    protected $respondTo = [];
    
    protected $actionName;
    
    protected $callbacks;
    
    protected $selfRefl;
    
    protected $appControllerRefls = [];
    
    protected $actionView;
    
    protected $application;
    
    /**
     * Children classes shouldn't override __construct(),
     * they should override init() instead.
     *
     * The classes "ApplicationController" should be abstract classes.
     * Some methods declared on them (init() and filters()) will be bound to the
     * actual instance and executed.
     * This will happen with any class called "ApplicationController" under any
     * namespace.
     */
    public function __construct($application)
    {
        $this->application = $application;
        $this->setUpAppComponents();
        $this->setUpReflections();
        $this->runInitializers();
    }
    
    protected function setUpReflections()
    {
        $class      = get_called_class();
        $reflection = $this->getReflection();
        while (true) {
            $parent = $reflection->getParentClass();
            if ($this->isApplicationController($parent->getName())) {
                $this->appControllerRefls[] = $parent;
            } elseif ($parent->getName() == __CLASS__) {
                break;
            }
            $reflection = $parent;
        }
        $this->appControllerRefls = array_reverse($this->appControllerRefls);
    }
    
    protected function setUpAppComponents()
    {
        $this->request  = $this->application->request();
        $this->response = $this->application->response();
        $this->params   = $this->application->parameters();
        $this->session  = $this->application->session();
        $this->routeSet = $this->application->routes();
    }
    
    public function __call($method, $params)
    {
        # TODO
        // if ($this->isNamedPathMethod($method)) {
            // return $this->getNamedPath($method, $params);
        // }
        
        throw new Exception\BadMethodCallException(
            sprintf(
                "Called to unknown method %s::%s",
                get_called_class(),
                $method
            )
        );
    }
    
    public function __set($property, $value)
    {
        $this->setAssign($property, $value);
    }
    
    public function __get($property)
    {
        return $this->getAssign($property);
    }
    
    /**
     * Due to the nature of this functionality, variables set here are
     * practically read-only, unless it's an object.
     */
    public function setAssign($name, $value)
    {
        $this->assigns()->set($name, $value);
    }
    
    public function getAssign($name)
    {
        return $this->assigns()->get($name);
    }
    
    public function assigns()
    {
        if (!$this->assigns) {
            $this->assigns = new Assigns();
        }
        return $this->assigns;
    }
    
    public function getReflection()
    {
        if (!$this->selfRefl) {
            $this->selfRefl = new ReflectionClass(get_called_class());
        }
        return $this->selfRefl;
    }
    
    public function response()
    {
        return $this->application->response();
    }
    
    public function session()
    {
        return $this->application->session();
    }
    
    public function params()
    {
        return $this->application->parameters();
    }
    
    public function request()
    {
        return $this->application->request();
    }
    
    public function routeSet()
    {
        return $this->application->routes();
    }
    
    public function cookies()
    {
        return $this->application->response()->cookieJar();
    }
    
    public function setLayout($layout)
    {
        $this->layout = $layout;
    }
    
    public function layout()
    {
        return $this->layout;
    }
    
    public function setCallbacks(Base\Callbacks $callbacks)
    {
        $this->callbacks = $callbacks;
    }
    
    public function callbacks()
    {
        return $this->callbacks;
    }
    
    public function helper()
    {
        foreach (func_get_args() as $helperClass) {
            HelperSet::addDefaultHelper($helperClass);
        }
    }
    
    public function respondTo(array $responses)
    {
        $this->respondTo = $responses;
    }
    
    public function respondsTo()
    {
        return $this->respondTo;
    }
    
    public function respondWith($var)
    {
        $this->respondWith = $var;
    }
    
    public function respondsWith()
    {
        return $this->respondWith;
    }
    
    /**
     * Render
     * When rendering 'action', the name of the action could be
     * camelCased or underscored.
     */
    public function render($options = [])
    {
        if ($this->isPerformed()) {
            throw new Exception\DoubleRenderException(
                'Can only render or redirect once per action'
            );
        }
        list ($renderOptions, $options) = $this->splitOptions($options);
        $renderOptions = $this->normalizeRender($renderOptions);
        $this->response()->setBody($this->renderToString($renderOptions, $options));
    }
    
    /**
     * If after calling actionView(), helper() is called, those
     * helpers won't be added to the HelperSet.
     */
    public function actionView()
    {
        if (!$this->actionView) {
            $this->actionView = new ActionView();
            
            # TODO: add default helpers.
            # make sure namespaced controllers get namespaced helpers added.
            HelperSet::addDefaultHelper('ApplicationHelper');
            
            $this->actionView->setHelperSet(
                new HelperSet(
                    $this->request(),
                    $this->params(),
                    $this->session(),
                    $this->assigns()
                )
            );
            # Set routeSet to baseHelper.
            $this->actionView->helperSet()->setRouteSet($this->routeSet());
            
        }
        return $this->actionView;
    }
    
    public function renderToString(array $renderOptions = [], array $options = [])
    {
        if ($this->isPerformed()) {
            throw new Exception\DoubleRenderException(
                'Can only render or redirect once per action'
            );
        }
        
        $this->processOptions($options);
        
        $rendering = new Rendering($this);
        return $rendering->renderResponse($renderOptions);
    }
    
    protected function splitOptions($options)
    {
        if (!$options) {
            return [[], []];
        } elseif (!is_array($options)) {
            return [$options, []];
        }
        
        $renderOptions = array_diff_key(
            $options,
            ['status' => null, 'contentType' => null, 'location' => null]
        );
        $options = array_diff_key($options, $renderOptions);
        
        return [$renderOptions, $options];
    }
    
    protected function processOptions(array $options)
    {
        if (!empty($options['status'])) {
            $this->response()->setStatus($options['status']);
        }
        if (!empty($options['contentType'])) {
            $this->response()->setContentType($options['contentType']);
        }
        if (!empty($options['location'])) {
            $this->response()->setLocation($this->urlFor($options['location']));
        }
    }
    
    protected function normalizeRender($renderOptions)
    {
        $renderOptions = $this->normalizeArgs($renderOptions);
        return $this->normalizeOptions($renderOptions);
    }
    
    protected function normalizeArgs($renderOptions = [])
    {
        $normalized = [];
        
        if ($renderOptions) {
            if (is_array($renderOptions)) {
                $normalized = $renderOptions;
            } elseif (is_string($renderOptions)) {
                if ($renderOptions == 'nothing') {
                    $normalized = ['nothing' => true];
                } else {
                    $barPos = strpos($renderOptions, '/');
                    
                    if ($barPos === 0 || strpos($renderOptions, ':') === 1) {
                        $key = 'file';
                    } elseif ($barPos !== false) {
                        $key = 'template';
                    } else {
                        $key = 'action';
                    }
                    
                    $normalized[$key] = $renderOptions;
                }
            }
        } else {
            $normalized = [
                'action' => $this->actionName
            ];
        }
        
        return $normalized;
    }
    
    protected function normalizeOptions($renderOptions)
    {
        if (!$renderOptions) {
            $renderOptions = [];
            $type  = null;
            $value = null;
        } else {
            $type  = key($renderOptions);
            $value = current($renderOptions);
        }
        
        if ($type == 'file') {
            if (isset($renderOptions['layout']) && $renderOptions['layout'] === true) {
                $renderOptions['layout'] = $this->layout();
            } else {
                $renderOptions['layout'] = false;
            }
        }
        
        if ($type == 'text' && !is_string($value)) {
            $renderOptions['text'] = (string)$renderOptions['text'];
        } elseif ($type) {
            $renderOptions['text'] = ' ';
        }
        
        if (!empty($renderOptions['partial'])) {
            if ($renderOptions['partial'] === true) {
                $renderOptions['partial'] = $this->actionName;
            }
        } else {
            $renderOptions['partial'] = false;
        }
        
        if (!in_array(['partial', 'file', 'template'], array_keys($renderOptions))) {
            if (empty($renderOptions['prefixes'])) {
                $renderOptions['prefixes'] = $this->controllerPrefixes();
            } elseif (!is_array($renderOptions['prefixes'])) {
                $renderOptions['prefixes'] = [$renderOptions['prefixes']];
            }
        }
        
        if (empty($renderOptions['template'])) {
            $inflector = $this->getService('inflector');
            if (!empty($renderOptions['action'])) {
                $renderOptions['template'] = $inflector->underscore($renderOptions['action'])->toString();
            } else {
                $renderOptions['template'] = $inflector->underscore($this->actionName)->toString();
            }
        }
        
        return $renderOptions;
    }
    
    protected function controllerPrefixes()
    {
        $reflection = $this->selfRefl;
        $prefixes   = [];
        $inflector  = $this->getService('inflector');
        $getPath    = function($class) use ($inflector) {
            return $inflector->underscore(preg_replace('/(\w+)Controller$/', '\1', $class))->toString();
        };
        
        $prefixes[] = $getPath(get_class($this));
        
        while (true) {
            $parent = $reflection->getParentClass();
            if ($this->isApplicationController($parent->getName())) {
                $prefixes[] = $getPath($parent->getName());
            } elseif ($parent->getName() == __CLASS__) {
                break;
            }
            $reflection = $parent;
        }
        
        return $prefixes;
    }
    
    /**
     * Pass an int or string and it will be taken as status.
     * Valid options:
     * - status
     * - location
     * - contentType
     * The rest of options will taken as header/value pairs.
     */
    public function head($options)
    {
        if (is_int($options) || is_string($options)) {
            $options = ['status' => $options];
        } elseif (!is_array($options)) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    "Argument must be either string or array, %s passed",
                    gettype($options)
                )
            );
        }
        
        if ($options) {
            $validOptions = ['status' => null, 'location' => null, 'contentType' => null];
            
            $headers = array_diff_key($options, $validOptions);
            $options = array_intersect_key($options, $validOptions);
            
            if (isset($options['status'])) {
                $this->response()->setStatus($options['status']);
            }
            if (isset($options['location'])) {
                $this->response()->setLocation($this->urlFor($options['location']));
            }
            
            foreach ($options as $key => $value) {
                $this->response()->addHeader(
                    str_replace(' ', '-', ucwords(strtolower(str_replace('-', ' ', $key)))),
                    $value
                );
            }
        }
        
        $status = $this->response()->status();
        
        if (!
            (($status > 100 && $status < 199) ||
            in_array($status, [204, 205, 304]))
        ) {
            $this->response()->setCharset(false);
            if (isset($options['contentType'])) {
                $this->response->setContentType($options['contentType']);
            }
            $this->response()->setBody(' ');
        } else {
            $this->response()->removeHeader('Content-Type');
            $this->response()->setBody('');
        }
    }
    
    public function redirectParams()
    {
        return $this->redirectParams;
    }
    
    public function urlFor($params)
    {
        $urlFor = new UrlFor($params);
        return $urlFor->url();
    }
    
    public function runAction($actionName)
    {
        $this->actionName = $actionName;
        
        if ($this->isActionMethodValid($actionName)) {
            $runner = new Base\CallbacksRunner($this);
            
            $runner->runRequestAction($actionName, function() {
                $this->{$this->actionName}();
            });
        } else {
            # TODO: check if view exists
            throw new Exception\UnknownActionException(
                sprintf(
                    "The action '%s' could not be found for %s",
                    $actionName,
                    get_called_class()
                )
            );
        }
        
    }
    
    # TODO: allow for whitelist action names
    # TODO: cache allowed methods list.
    protected function isActionMethodValid($methodName)
    {
        // try {
            if (!$this->selfRefl->hasMethod($methodName)) {
                return false;
            }
            $method = $this->selfRefl->getMethod($methodName);
            
            return
                // $method->getDeclaringClass()->getName() == $this->selfRefl->getName() &&
                $method->isPublic();
        // } catch (\ReflectionException $e) {
            // return false;
        // }
    }
    
    public function actionName()
    {
        return $this->actionName;
    }
    
    /**
     * Searches through all the ApplicationControllers classes for a method,
     * and returns them all.
     *
     * @return array
     */
    public function getAppControllersMethod($methodName, $scope = '')
    {
        $methods = [];
        
        if ($this->appControllerRefls) {
            foreach ($this->appControllerRefls as $appRefl) {
                if ($appRefl->hasMethod($methodName)) {
                    $method = $appRefl->getMethod($methodName);
                    
                    if ($this->isApplicationController($method->getDeclaringClass()->getName())) {
                        if ($scope) {
                            $isScope = 'is' . ucfirst($scope);
                            if (!$method->$isScope()) {
                                continue;
                            }
                        }
                        $methods[] = $method->getClosure($this);
                    }
                }
            }
        }
        
        return $methods;
    }
    
    public function redirectTo($location, array $options = [])
    {
        if ($this->response()->body()) {
            throw new Exception\DoubleRenderException(
                "Can call either render or redirectTo only once per action."
            );
        }
        
        $location = $this->computeRedirectToLocation($location);
        $this->response()->setLocation($location);
        
        if (isset($options['status'])) {
            $this->response()->setStatus($location);
        }
        
        $this->response()->setBody(
            '<html><body>You are being <a href="' .
            $location .
            '">redirected</a>.</body></html>'
        );
    }
    
    public function isPerformed()
    {
        return $this->response()->body() !== null || $this->response()->isCommitted();
    }
    
    /**
     * Automatically finds the corresponding resource for the current
     * controller based on the 'id' parameter. For this to work, the
     * resource class (by default, the singularized name of the controller class
     * without the Controller part) must be an instance of Rails\ActiveRecord\Base.
     * The ARBase::find() method is called, therefore if the resource isn't found,
     * 404 is returned.
     * The resource is set to the assigns, and also returned.
     * This method could be set as a before-filter.
     *
     * @param string $param     The request parameter
     * @param string $resClass  The resource class
     */
    protected function findResource($param = 'id', $resClass = null)
    {
        if (!$resClass) {
            $class = get_called_class();
            if (is_int(strpos($class, '\\'))) {
                $classParts = explode('\\', $class);
                $class     = end($classParts);
            }
            $resClass = $this->getService('inflector')->singularize(substr($class, 0, -10))->toString();
        }
        
        $resource = $resClass::find($this->params()->$param);
        
        $prop = lcfirst($resClass);
        $this->assigns()->set($prop, $resource);
        return $resource;
    }
    
    protected function init()
    {
    }
    
    protected function filters()
    {
        return [];
    }
    
    /**
     * Runs initializers from the called class and its
     * ApplicationController parents, if any.
     */
    protected function runInitializers()
    {
        $methodName = 'init';
        
        # Run ApplicationController's init method.
        if ($inits = $this->getAppControllersMethod($methodName)) {
            foreach ($inits as $init) {
                $init = $init->bindTo($this);
                $init();
            }
        }
        
        $method = $this->getReflection()->getMethod($methodName);
        if ($method->getDeclaringClass()->getName() == get_called_class()) {
            $this->$methodName();
        }
    }
    
    protected function isApplicationController($class)
    {
        return
            strpos($class, self::APP_CONTROLLER_CLASS) ===
            (strlen($class) - strlen(self::APP_CONTROLLER_CLASS));
    }
    
    protected function computeRedirectToLocation($params)
    {
        return $this->routeSet->urlFor($params);
    }
}
