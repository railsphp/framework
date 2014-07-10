<?php
namespace Rails\Generator;

use ReflectionClass;
use ReflectionMethod;
use Rails\Console\Task\Task;

abstract class Generator extends Task
{
    abstract protected function template();
    
    abstract protected function filePath();
    
    protected function createFile()
    {
        ob_start();
        require $this->template();
        $contents = ob_get_clean();
        
        $this->createPath();
        
        file_put_contents($this->fullFilePath(), $contents);
    }

    protected function phpOpenTag()
    {
        return "<?php\n";
    }
    
    protected function createPath()
    {
        $fullPath = dirname($this->fullFilePath());
        
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
    }
    
    protected function fullFilePath()
    {
        return \Rails::root() . '/' . $this->filePath();
    }

    protected function task()
    {
        $generatorClass = get_called_class();
        $reflection = new ReflectionClass($generatorClass);
        
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() == $generatorClass) {
                $methodName = $method->getName();
                $this->$methodName();
            }
        }
    }
}
