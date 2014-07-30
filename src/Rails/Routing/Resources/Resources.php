<?php
namespace Rails\Routing\Resources;

use Closure;
use ArrayObject;
use Rails\Routing\Route\Route;
use Rails\Routing\Exception;
use Rails\Routing\Route\RouteSet;

/*
TODO: support this:


$this->scope(['path' => 'dashboard', 'controller' => 'dashboard'], function() {
    // Since in the scope the controller is being set, one or all of the following
    // should be accepted:
    
    // No slash is given, the path is assumed to tbe action.
    // So it can ignore the second string argument and pass options directly.
    $this->get('status_update', [ ...options ]);
    // Set the action explicity, ommiting the controller.
    $this->get('status_update', '#status_update');
    // Pass options as second argument, setting the action as an option.
    $this->get('list_activities', ['action' => 'list_activities']);
});
*/
class Resources
{
    protected $routeNames = [];
    
    protected $set;
    
    protected $scope = [
        'path' => null,
        'shallow' => null,
        'shallowPath' => null,
        'shallowPrefix' => null,
        'scopeLevel' => null,
        'controller' => null,
        'action' => null,
        'scopeLevelResource' => null,
        'as' => null,
        'namespace' => [],
        'options' => [],
        'pathNames' => [],
        'constraints' => [],
        'defaults' => [],
    ];
    
    public static function validOnOptions()
    {
        return ['new', 'collection', 'member'];
    }
    
    public static function resourceOptions()
    {
        return [
            'as', 'controller', 'path',
            'only', 'except', 'param', 'concerns'
        ];
    }
    
    public static function canonicalActions()
    {
        return [
            'index', 'create', 'new',
            'show', 'update', 'destroy'
        ];
    }
    
    public static function resourceMethodScopes()
    {
        return ['collection', 'member', 'new'];
    }
    
    public static function resourceScopes()
    {
        return ['resouce', 'resources'];
    }
    
    public static function scopeOptions()
    {
        return [
            'path', 'shallowPath', 'as', 'shallowPrefix',
            'namespace', 'controller', 'action', 'pathNames',
            'constraints', 'shallow', 'blocks', 'defaults', 'options'
        ];
    }
    
    public function __construct(RouteSet $set = null)
    {
        if (!$set) {
            $set = new RouteSet();
        }
        $this->set = $set;
    }
    
    public function draw(Closure $builder)
    {
        $builder = $builder->bindTo($this);
        $builder();
        return $this;
    }
    
    public function normalizePath($path)
    {
        $path = trim($path, '/') ?: '/';
        if (substr($path, 0, 1) != '/') {
            $path = '/' . $path;
        }
        if (!preg_match('~^/\(+[^)]+\)$~', $path)) {
            $path = preg_replace('~/(\(+)/?~', '\1/', $path);
        }
        return $path;
    }
    
    public function normalizeName($name)
    {
        return preg_replace_callback(
            '/_([a-z])/',
            function($m) {
                return strtoupper($m[1]);
            },
            str_replace('/', '_', substr($this->normalizePath($name), 1))
        );
    }
    
    public function set()
    {
        return $this->set;
    }
    
    public function resources($resources, $options = [], Closure $block = null)
    {
        $resources = (array)$resources;
        if ($options instanceof Closure) {
            $block = $options;
            $options = [];
        }
        
        if ($this->applyCommonBehaviourFor('resources', $resources, $options, $block)) {
            return $this;
        }
        
        $this->resourceScope(
            'resources',
            new Resource(array_pop($resources), $options),
            function() use ($options, $block) {
                if ($block) {
                    $block();
                }
                
                if (!empty($options['concerns'])) {
                    $this->concerns($options['concerns']);
                }
                
                $this->collection(function() {
                    if ($this->parentResource() && in_array('index', $this->parentResource()->actions())) {
                        $this->get('index');
                    }
                    if ($this->parentResource() && in_array('create', $this->parentResource()->actions())) {
                        $this->post('create');
                    }
                });
                
                if (in_array('new', $this->parentResource()->actions())) {
                    $this->addNew(function() {
                        $this->get('new');
                    });
                }
                
                $this->setMemberMappingsForResource();
            }
        );
        
        return $this;
    }
    
    public function collection(Closure $block)
    {
        if (!$this->inResourceScope()) {
            throw new Exception\LogicException(
                "Can't use collection outside resource(s) scope"
            );
        }
        
        $this->withScopeLevel('collection', function() use ($block) {
            $this->scope($this->parentResource()->collectionScope(), $block);
        });
    }
    
    public function member(Closure $block)
    {
        if (!$this->inResourceScope()) {
            throw new Exception\LogicException(
                "Can't use member outside resource(s) scope"
            );
        }
        
        $this->withScopeLevel('member', function() use ($block) {
            $this->scope($this->parentResource()->memberScope(), $block);
        });
    }
    
    public function addNew(Closure $block)
    {
        if (!$this->inResourceScope()) {
            throw new Exception\LogicException(
                "Can't use addNew outside resource(s) scope"
            );
        }
        
        $this->withScopeLevel('new', function() use ($block) {
            $this->scope(
                [
                    'path'   => $this->parentResource()->newScope($this->actionPath('new')),
                    'action' => 'blank'
                ],
                $block
            );
        });
    }
    
    public function nested(Closure $block)
    {
        if (!$this->inResourceScope()) {
            throw new Exception\LogicException(
                "Can't use nested outside resource(s) scope"
            );
        }
        
        $this->withScopeLevel('nested', function() use ($block) {
            if ($this->inShallow()) {
                if (empty($this->scope['shallowPath'])) {
                    $this->scope(
                        $this->parentResource()->nestedScope(),
                        $this->nestedOptions(),
                        $block
                    );
                } else {
                    $this->scope(
                        $this->scope['shallowPath'],
                        ['as' => $this->scope['shallowPrefix']],
                        function() use ($block) {
                            $this->scope(
                                $this->parentResource()->nestedScope(),
                                $this->nestedOptions(),
                                function() use ($block) {
                                    $block();
                                }
                            );
                        }
                    );
                }
            } else {
                $this->scope(
                    $this->parentResource()->nestedScope(),
                    $this->nestedOptions(),
                    function() use ($block) {
                        $block();
                    }
                );
            }
        });
    }
    
    public function root($path, $options = [])
    {
        if (is_string($path)) {
            $options['to'] = $path;
        } elseif (is_array($path) && !$options) {
            $options = $path;
        } else {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    "Arguments must be path and/or options"
                )
            );
        }
        
        if ($this->scope['scopeLevel'] == 'resources') {
            $this->withScopeLevel('root', function() use ($options) {
                $this->scope($this->parentResource()->path(), function() {
                    $this->addRoot($options);
                });
            });
        } else {
            $this->addRoot($options);
        }
    }
    
    public function namespaced($path, $options = [], $block = null)
    {
        if ($options instanceof Closure) {
            $block = $options;
            $options = [];
        }
        
        if ($this->inResourceScope()) {
            $this->nested(function() use ($path, $options, $block) {
                $this->addNamespaced($path, $options, $block);
            });
        } else {
            $this->addNamespaced($path, $options, $block);
        }
    }
    
    public function controller($controller, $options = [], $block = null)
    {
        if ($options instanceof Closure) {
            $block = $options;
            $options = [];
        }
        $options['controller'] = $controller;
        $this->scope($options, $block);
    }
    
    /**
     * Scope
     * <pre>
     * $res->scope('user/panel', ['constraints' => ['id' => '/\d+/']], function() { ... });
     * $res->scope(['user', 'panel'], ['constraints' => ['id' => '/\d+/']], function() { ... });
     * $res->scope(['user', 'panel', 'constraints' => ['id' => '/\d+/']], function() { ... });
     * $res->scope(['path' => 'user/panel', 'constraints' => ['id' => '/\d+/']], function() { ... });
     * </pre>
     */
    public function scope($paths, $options = [], Closure $block = null)
    {
        if ($options instanceof Closure) {
            $block   = $options;
            $options = [];
        }
        
        $options = array_merge((array)$paths, (array)$options);
        
        $paths = [];
        foreach ($options as $k => $v) {
            if (is_int($k)) {
                $paths[] = $v;
                unset($options[$v]);
            }
        }
        
        if ($paths) {
            $options['path'] = implode('/', $paths);
        }
        
        $recover = [];
        
        if (!isset($options['constraints'])) {
            $options['constraints'] = [];
        }
        if (!isset($options['defaults'])) {
            $options['defaults'] = [];
        }
        
        if (is_array($options['constraints'])) {
            $defaults = [];
            foreach ($options['constraints'] as $key => $value) {
                if (in_array($key, self::urlOptions()) && (!is_string($value) || strpos($value, 0, 1) != '/')) {
                    $defaults[$key] = $value;
                    unset($options['constraints'][$key]);
                }
            }
            $options['defaults'] = array_merge($defaults, $options['defaults']);
        } else {
            $constraintsBlock = $options['constraints'];
            $options['constraints'] = [];
        }
        
        foreach (self::scopeOptions() as $option) {
            if ($option == 'blocks') {
                $value = isset($constraintsBlock) ? $constraintsBlock : null;
            } elseif ($option === 'options') {
                $value = $options;
            } else {
                if (array_key_exists($option, $options)) {
                    $value = $options[$option];
                    unset($options[$option]);
                } else {
                    $value = null;
                }
            }
            
            if ($value) {
                $recover[$option] = $this->scope[$option];
                $methodName = implode('', array_map(function($x) { return ucfirst($x); }, explode(' ', $option)));
                $method = 'merge' . $methodName . 'Scope';
                $this->scope[$option] = $this->$method($this->scope[$option], $value);
            }
        }
        
        $block();
        $this->scope = array_merge($this->scope, $recover);
        
        return $this;
    }
    
    public function constraints($constraints, $block = null)
    {
        if ($constraints instanceof Closure) {
            $block = $constraints;
            $constraints = [];
        }
        $this->scope(['constraints' => $constraints], $block);
    }
    
    public function defaults($defaults, $block = null)
    {
        if ($defaults instanceof Closure) {
            $block = $defaults;
            $defaults = [];
        }
        $this->scope(['defaults' => $defaults], $block);
    }
    
    public function decomposedMatch($path, $options)
    {
        if (isset($options['on'])) {
            $on = $options['on'];
            if ($on == 'new') {
                $on = 'addNew';
            }
            unset($options['on']);
            $this->$on(function() use ($path, $options) {
                $this->decomposedMatch($path, $options);
            });
        } else {
            switch ($this->scope['scopeLevel']) {
                case 'resources':
                    $this->nested(function() use ($path, $options) {
                        $this->decomposedMatch($path, $options);
                    });
                    break;
                case 'resource':
                    $this->member(function() use ($path, $options) {
                        $this->decomposedMatch($path, $options);
                    });
                    break;
                default:
                    $this->addRoute($path, $options);
                    break;
            }
        }
    }
    
    public function addRoute($action, $options)
    {
        $path = $this->pathForAction($action, $options['path']);
        unset($options['path']);
        
        if (preg_match('/^[\w\/]+$/', $action)) {
            if (is_bool(strpos($action, '/'))) {
                if (!isset($options['action'])) {
                    $options['action'] = $action;
                }
            }
        } else {
            $action = null;
        }
        
        if (empty($options['as'])) {
            $options['as'] = null;
            $options['as'] = $this->nameForAction($options['as'], $action);
        }
        $options['alias'] = $options['as'];
        unset($options['as']);
        
        $scope   = $this->scope;
        $options = array_merge_recursive($scope['options'], $options);
        unset($scope['options']);
        $route = new Route($this->escapePath($path), $options, array_filter($scope));
        $this->set[] = $route;
    }
    
    /**
     * Defines a concern.
     */
    public function concern($name, Closure $block)
    {
        $this->concerns[$name] = $block;
    }
    
    /**
     * Execute one or more concerns.
     *
     * @param string|array $names
     */
    public function concerns($names, array $options = [])
    {
        $names = (array)$names;
        
        foreach ($names as $name) {
            if (isset($this->concerns[$name])) {
                $this->concerns[$name]($this, $options);
            } else {
                throw new Exception\InvalidArgumentException(
                    sprintf("No concern named '%s' was found!", $name)
                );
            }
        }
    }
    
    public function match($path, $to = null, array $options = [])
    {
        if (is_array($to)) {
            $options = $to;
            $to = null;
        }
        
        if (empty($options['to'])) {
            $options['to'] = $to;
        }
        
        if (!isset($options['anchor'])) {
            $options['anchor'] = true;
        }
        
        if (isset($options['on']) && !in_array($options['on'], self::validOnOptions())) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    'Unknown scope given to "on" option'
                )
            );
        }
        
        if ($this->scope['controller'] && $this->scope['action']) {
            if (!$options['to']) {
                $options['to'] = $this->scope['controller'] . '#' . $this->scope['action'];
            }
        }
        
        $routeOptions = $options;
        if (!isset($routeOptions['path']) && is_int(strpos($path, '/'))) {
            $routeOptions['path'] = $path;
        } else {
            $routeOptions['path'] = null;
        }
        
        $pathWithoutFormat = preg_replace('/\(\.:format\)$/', '', $path);
        
        if ($this->isUsingMatchShorthand($pathWithoutFormat, $routeOptions)) {
            if (!$routeOptions['to']) {
                $routeOptions['to'] = $this->camelize($pathWithoutFormat);
            }
        } elseif (
            preg_match('/^\w+$/', $pathWithoutFormat) &&
            !isset($routeOptions['action'])
        ) {
            /**
             Expected cases:
              - When in a scope that sets a default controller a
                route is defined like:
                $this->get('some_action');
                So the token will be: controller#someAction
             */
            $routeOptions['action'] = $this->camelize($pathWithoutFormat);
        }
        $this->decomposedMatch($path, $routeOptions);
        
        return $this;
    }
    
    public function get($path, $to = null, array $options = [])
    {
        $this->mapMethod('get', $path, $to, $options);
        return $this;
    }
    
    public function post($path, $to = null, array $options = [])
    {
        $this->mapMethod('post', $path, $to, $options);
        return $this;
    }
    
    public function patch($path, $to = null, array $options = [])
    {
        $this->mapMethod('patch', $path, $to, $options);
        return $this;
    }
    
    public function put($path, $to = null, array $options = [])
    {
        $this->mapMethod('put', $path, $to, $options);
        return $this;
    }
    
    public function delete($path, $to = null, array $options = [])
    {
        $this->mapMethod('delete', $path, $to, $options);
        return $this;
    }
    
    protected function mapMethod($method, $path, $to = null, array $options = [])
    {
        if (is_array($to)) {
            $options = $to;
            $to = null;
        }
        $options['via'] = [$method];
        $this->match($path, $to, $options);
    }
    
    protected function addRoot($options)
    {
        $this->match('/', array_merge(['as' => 'root', 'via' => 'get'], $options));
    }
    
    protected function addNamespaced($path, $options = [], $block = null)
    {
        $options = array_merge([
            'path' => $path,
            'as' => $path,
            'namespace' => $path,
            'shallowPath' => $path,
            'shallowPrefix' => $path,
        ], $options);
        
        $this->scope($options, $block);
    }
    
    protected function applyCommonBehaviourFor($method, $resources, $options, $block)
    {
        if (count($resources) > 1) {
            foreach ($resources as $r) {
                $this->$method($r, $options, $block);
            }
            return true;
        }
        
        if ($this->inResourceScope()) {
            $this->nested(function() use ($method, $resources, $options, $block) {
                $this->$method(array_pop($resources), $options, $block);
            });
            return true;
        }
        
        if (!isset($options['constraints'])) {
            $options['constraints'] = [];
        }
        foreach ($options as $key => $value) {
            if (is_string($value) && substr($value, 0, 1) == '/') {
                $options['constraints'][$k] = $value;
                unset($options[$key]);
            }
        }
        
        $options = array_filter($options);
        $scopeOptions = array_diff_key($options, array_fill_keys(self::resourceOptions(), null));
        if ($scopeOptions) {
            $this->scope($scopeOptions, function() use ($method, $resources, $options, $block) {
                $this->$method(array_pop($resources), $options, $block);
            });
            return true;
        }
        
        if (!$this->hasActionOptions($options)) {
            if ($this->hasScopeActionOptions()) {
                $options = call_user_func_array('array_merge', array_merge($options, $this->scopeActionOptions()));
            }
        }
        
        return false;
    }
    
    protected function hasActionOptions($options)
    {
        return isset($options['only']) || isset($options['except']);
    }
    
    protected function hasScopeActionOptions()
    {
        return !empty($this->scope['options']) && $this->hasActionOptions($this->scope['options']);
    }
    
    protected function scopeActionOptions()
    {
        $ret = [];
        if (!empty($this->scope['options']['only'])) {
            $ret['only'] = $this->scope['options']['only'];
        }
        if (!empty($this->scope['options']['except'])) {
            $ret['except'] = $this->scope['options']['except'];
        }
        return $ret;
    }
    
    protected function inResourceScope()
    {
        return in_array($this->scope['scopeLevel'], self::resourceScopes());
    }
    
    protected function resourceScope($kind, $resource, Closure $block = null)
    {
        $this->withScopeLevel($kind, $resource, function() use ($block) {
            $this->scope($this->parentResource()->resourceScope(), $block);
        });
    }
    
    protected function withScopeLevel($kind, $resource = null, Closure $block = null)
    {
        if ($resource instanceof Closure) {
            $block = $resource;
            $resource = null;
        }
        if (!$resource) {
            $resource = $this->parentResource();
        }
        
        $old = $this->scope['scopeLevel'];
        $this->scope['scopeLevel'] = $kind;
        
        $oldResource = $this->scope['scopeLevelResource'];
        $this->scope['scopeLevelResource'] = $resource;
        
        if ($block) {
            $block();
        }
        
        $this->scope['scopeLevel'] = $old;
        $this->scope['scopeLevelResource'] = $oldResource;
    }
    
    protected function actionPath($name, $path = null)
    {
        if ($path) {
            return $path;
        } elseif (isset($this->scope['pathNames'][$name])) {
            return $this->scope['pathNames'][$name];
        } else {
            return $name;
        }
    }
    
    protected function mergePathScope($parent, $child)
    {
        return $this->normalizePath($parent . '/' . $child);
    }
    
    protected function mergeShallowPathScope($parent, $child)
    {
        return $this->normalizePath($parent . '/' . $child);
    }
    
    protected function mergeAsScope($parent, $child)
    {
        return $parent ? $parent . '_' . $child : $child;
    }
    
    protected function mergeShallowPrefixScope($parent, $child)
    {
        return $parent ? $parent . '_' . $child : $child;
    }
    
    protected function mergeNamespaceScope($parent, $child)
    {
        return $parent ? $parent . '/' . $child : $child;
    }
    
    protected function mergeControllerScope($parent, $child)
    {
        return $child;
    }
    
    protected function mergeActionScope($parent, $child)
    {
        return $child;
    }
    
    protected function mergePathNamesScope($parent, $child)
    {
        return $this->mergeOptionsScope($parent, $child);
    }
    
    protected function mergeConstraintsScope($parent, $child)
    {
        return $this->mergeOptionsScope($parent, $child);
    }
    
    protected function mergeDefaultsScope($parent, $child)
    {
        return $this->mergeOptionsScope($parent, $child);
    }
    
    protected function mergeBlocksScope($parent, $child)
    {
        $merged = $parent ?: [];
        if ($child) {
            $merged[] = $child;
        }
        return $merged;
    }
    
    protected function mergeOptionsScope($parent, $child)
    {
        $diff = array_diff_key($parent, $this->overrideKeys($child));
        return array_merge($diff, $child);
    }
    
    protected function mergeShallowScope($parent, $child)
    {
        return (bool)$child;
    }
    
    protected function overrideKeys($child)
    {
        if (isset($child['only']) || isset($child['except'])) {
            return ['only' => null, 'except' => null];
        } else {
            return [];
        }
    }
    
    protected function isUsingMatchShorthand($path, $options)
    {
        return $path &&
               (!empty($options['to']) || empty($options['action'])) &&
               preg_match('~/[\w/]+$~', $path);
    }
    
    protected function pathForAction($action, $path)
    {
        $prefix = $this->inShallowScoping() ?
            $this->scope['shallowPath'] . '/' . $this->parentResource()->shallowScope() : $this->scope['path'];
        
        if ($this->isCanonicalAction($action, empty($path))) {
            return $prefix;
        } else {
            return $prefix . '/' . $this->actionPath($action, $path);
        }
    }
    
    protected function isCanonicalAction($action, $flag)
    {
        return $flag && $this->inResourceMethodScope() && in_array($action, self::canonicalActions());
    }
    
    protected function inResourceMethodScope()
    {
        return in_array($this->scope['scopeLevel'], self::resourceMethodScopes());
    }
    
    protected function inShallowScoping()
    {
        return $this->inShallow() && $this->scope['scopeLevel'] == 'member';
    }
    
    protected function inShallow()
    {
        return $this->parentResource() instanceof Resource && $this->scope['shallow'];
    }
    
    protected function nestedOptions()
    {
        $options = [ 'as' => $this->parentResource()->memberName() ];
        if ($this->hasParamConstraint()) {
            $options['constraints'] = [
                $this->parentResource()->nestedParam() => $this->paramConstraint()
            ];
        }
        return $options;
    }
    
    protected function hasParamConstraint()
    {
        return isset($this->scope['constraints'][$this->parentResource()->param()]);
    }
    
    protected function paramConstraint()
    {
        return $this->scope['constraints'][$this->parentResource()->param()];
    }
    
    protected function parentResource()
    {
        return $this->scope['scopeLevelResource'];
    }
    
    protected function setMemberMappingsForResource()
    {
        $this->member(function() {
            if (in_array('edit', $this->parentResource()->actions())) {
                $this->get('edit');
            }
            if (in_array('show', $this->parentResource()->actions())) {
                $this->get('show');
            }
            if (in_array('update', $this->parentResource()->actions())) {
                $this->match('update', ['via' => ['patch', 'put']]);
            }
            if (in_array('destroy', $this->parentResource()->actions())) {
                $this->delete('destroy');
            }
        });
    }
    
    protected function nameForAction($as, $action)
    {
        $prefix = $this->prefixNameForAction($as, $action);
        if ($prefix) {
            $prefix = $this->normalizeName($prefix);
        }
        $namePrefix = $this->scope['as'];
        
        if ($this->parentResource()) {
            if (!($as || $action)) {
                return;
            }
            
            $collectionName = $this->parentResource()->collectionName();
            $memberName     = $this->parentResource()->memberName();
        } else {
            $collectionName = null;
            $memberName     = null;
        }
        
        switch ($this->scope['scopeLevel']) {
            case 'nested':
                $name = [$namePrefix, $prefix];
                break;
            case 'collection':
                $name = [$prefix, $namePrefix, $collectionName];
                break;
            case 'new':
                $name = [$prefix, 'new', $namePrefix, $memberName];
                break;
            case 'member':
                $name = [
                    $prefix,
                    ($this->inShallowScoping() ? $this->scope['shallowPrefix'] : $namePrefix),
                    $memberName
                ];
                break;
            case 'root':
                $name = [$namePrefix, $collectionName, $prefix];
                break;
            default:
                $name = [$namePrefix, $memberName, $prefix];
                break;
        }
        
        if ($candidate = lcfirst(implode(array_map(function($x){ return ucfirst($x); }, array_filter($name))))) {
            if (!$as) {
                if (!in_array($candidate, $this->routeNames) && preg_match('/\A[_a-z]/i', $candidate)) {
                    $this->routeNames[] = $candidate;
                    return $candidate;
                }
            } else {
                $this->routeNames[] = $candidate;
                return $candidate;
            }
        }
    }
    
    protected function prefixNameForAction($as, $action)
    {
        if ($as) {
            return $as;
        } elseif (!$this->isCanonicalAction($action, $this->scope['scopeLevel'])) {
            return $action;
        }
    }
    
    protected function escapePath($path)
    {
        return str_replace(
            [
                '%28',
                '%29',
                '%3A',
                '%2A',
                '%2F',
            ],
            [
                '(',
                ')',
                ':',
                '*',
                '/',
            ],
            urlencode($path)
        );
    }
    
    protected function camelize($string)
    {
        return preg_replace_callback(
            '/_(\w)/',
            function($m) { return strtoupper($m[1]); },
            preg_replace(
                ['/^\//', '~/([^/]*)$~'],
                ['', '#\1'],
                $string
            )
        );
    }
}
