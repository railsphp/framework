<?php
namespace Rails\Routing\Route;

class Matcher
{
    /**
     * $path must not include query string.
     */
    public function match(BuiltRoute $route, $path, $verb)
    {
        /**
         * Normalize path. Same functionality is found
         * in Route#normalizePath.
         */
        $normalizedPath = trim($path, '/') ?: '/';
        if (substr($normalizedPath, 0, 1) != '/') {
            $normalizedPath = '/' . $normalizedPath;
        }
        
        if (!in_array(strtolower($verb), $route->via())) {
            return false;
        }
        
        $regex = $route->pathRegex();
        
        if (!preg_match($regex, $normalizedPath, $m)) {
            return false;
        }
        
        array_shift($m);
        
        $vars      = $route->vars();
        $params    = [];
        $varsNames = array_keys($vars);
        
        foreach ($m as $k => $value) {
            if (isset($vars[$varsNames[$k]]['constraint'])) {
                if (substr($vars[$varsNames[$k]]['constraint'], 0, 1) == '/') {
                    if (!preg_match($vars[$varsNames[$k]]['constraint'], $value)) {
                        return false;
                    }
                } else {
                    if ($value !== (string)$vars[$varsNames[$k]]['constraint']) {
                        return false;
                    }
                }
            }
            $params[$varsNames[$k]] = $value;
        }
        
        /**
         * Fill missing route variables with their
         * default values, if any.
         */
        $missingVars = array_diff($varsNames, array_keys($params));
        if ($missingVars && $route->defaults()) {
            foreach ($route->defaults() as $varName => $value) {
                if (in_array($varName, $missingVars)) {
                    $params[$varName] = $value;
                }
            }
        }
        
        return $params;
    }
    
    public function buildPath(BuiltRoute $route, array &$vars)
    {
        $builtPath = $route->path();
        
        if (!$this->checkRequiredVars($route, $builtPath, $vars)) {
            return false;
        }
        
        foreach ($route->optionalGroups() as $group) {
            $parsedGroup = $this->checkOptionalGroup($route, $group, $vars);
            $builtPath   = str_replace($group['part'], $parsedGroup, $builtPath);
        }
        
        # Remove remaining parentheses.
        $builtPath = str_replace(['(', ')'], '', $builtPath);
        
        return $builtPath;
    }
    
    protected function checkRequiredVars($route, &$builtPath, &$vars)
    {
        $routeVars = $route->vars();
        
        foreach ($route->requiredVars() as $reqVar) {
            if (!isset($vars[$reqVar])) {
                return false;
            } else {
                $builtPath = str_replace(
                    $routeVars[$reqVar]['type'] . $reqVar,
                    $vars[$reqVar],
                    $builtPath
                );
                unset($vars[$reqVar]);
            }
        }
        return true;
    }
    
    protected function checkOptionalGroup($route, $group, &$vars)
    {
        # Used for workaround.
        $action = false;
        
        $groupStr = $part = $group['part'];
        unset($group['part']);
        
        $mainVars = array_shift($group) ?: [];
        
        if (!$mainVars) {
            $groupStr = '';
        }
        
        $routeVars = $route->vars();
        
        foreach ($mainVars as $mainVar) {
            if (!isset($routeVars[$mainVar])) {
                continue;
            }
            
            if (!isset($vars[$mainVar])) {
                $groupStr = '';
                break;
            } else {
                # For workaround
                if ($mainVar == 'action') {
                    $action = $vars['action'];
                }
                $groupStr = str_replace(
                    $routeVars[$mainVar]['type'] . $mainVar,
                    $vars[$mainVar],
                    $groupStr
                );
                unset($vars[$mainVar]);
            }
        }
        
        if ($groupStr) {
            foreach ($group as $subgroup) {
                $subpart = $subgroup['part'];
                $subrepl = $this->checkOptionalGroup($route, $subgroup, $vars);
                # For workaround
                if (!$subrepl) {
                    array_shift($group);
                }
                $groupStr = str_replace($subpart, $subrepl, $groupStr);
            }
        }
        
        /**
         * Here comes the workaround. If the group has only one var
         * and it's "action" and its value is "index" and there are no
         * group memebers left (i.e., this current group is just something with
         * 'index'), remove it.
         */
        if (count($mainVars) == 1 && $mainVars[0] == 'action' && $action == 'index' && !$group) {
            $groupStr = '';
        }
        return $groupStr;
    }
}
