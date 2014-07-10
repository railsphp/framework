<?php
namespace Rails\ActiveSupport;

use Closure;
use ReflectionClass;

class ParentMethods
{
    /**
     * Get all method names of methods matching $methodMatcher.
     * $methodMatcher may be either a Closure or a string. If a Closure is passed,
     * a \ReflectionMethod will be passed as parameter for validation, and if the
     * Closure returns `true`, that method's name will be added.
     
     *
     * @param string $className
     * @param string|Closure $methodMatcher
     * @param string $excludedCLass
     */
    public function getNames($className, $methodMatcher, $excludedClass = null)
    {
        $this->normalizeMatcher($methodMatcher);
        $names = [];
        
        $this->iterateParentMethods(
            $className,
            function($method, $currentClass) use ($methodMatcher, &$names) {
                if ($methodMatcher($method, $currentClass)) {
                    $names[] = $method->getName();
                }
            },
            $excludedClass
        );
        
        return $names;
    }
    
    /**
     * @param string $className
     * @param string|Closure $methodMatcher
     * @param string $excludedCLass
     */
    public function getClosures($className, $methodMatcher, $excludedClass = null)
    {
        $this->normalizeMatcher($methodMatcher);
        $closures = [];
        
        $this->iterateParentMethods(
            $className,
            function($method, $currentClass) use ($methodMatcher, &$closures) {
                if ($methodMatcher($method, $currentClass)) {
                    $method->setAccessible(true);
                    $cloures[] = $method->getClosure();
                }
            },
            $excludedClass
        );
        
        return $closures;
    }
    
    /**
     * @param string $className
     * @param Closure $manipulator
     * @param string|null $excludedClass
     */
    public function iterateParentMethods($className, Closure $manipulator, $excludedClass = null)
    {
        $currentClass = new ReflectionClass($className);
        
        while (true) {
            foreach ($currentClass->getMethods() as $method) {
                # Methods declared by the excludedClass are ignored.
                if ($excludedClass && $method->getDeclaringClass()->getName() != $excludedClass) {
                    $manipulator($method, $currentClass);
                }
            }
            
            $currentClass = $currentClass->getParentClass();
            
            if (!$currentClass) {
                break;
            } elseif ($excludedClass && $currentClass->getName() == $excludedClass) {
                break;
            }
        }
    }
    
    protected function normalizeMatcher(&$methodMatcher)
    {
        if (is_string($methodMatcher)) {
            $matchingName  = $methodMatcher;
            $methodMatcher = function($method) use ($matchingName) {
                return $method->getName() == $matchingName;
            };
        } elseif (!$methodMatcher instanceof Closure) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    "\$methodMatcher must be either string or Closure, '%s' passed",
                    gettype($methodMatcher)
                )
            );
        }
    }
}
