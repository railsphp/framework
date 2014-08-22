<?php
namespace Rails\ActionView\Generators\Helper;

use Rails\Generator\Generator;

class HelperGenerator extends Generator
{
    protected $classNamespace;
    
    protected $className;
    
    public function extractNamespace()
    {
        $name = $this->arg('name');
        if (strpos($name, '/') !== false) {
            $this->classNamespace = substr($name, 0, strrpos($name, '/'));
            $this->className = substr($name, strrpos($name, '/') + 1);
        } else {
            $this->className = $name;
        }
        $this->className .= 'Helper';
    }
    
    public function writeFile()
    {
        $this->createGenerator();
    }
    
    protected function defineNamespace()
    {
        if ($this->classNamespace) {
            return 'namespace ' . $this->classNamespace . ";\n\n";
        }
    }
    
    protected function baseClass()
    {
        $baseClass = 'Rails\ActionView\Helper';
        if ($this->classNamespace) {
            $baseClass = '\\' . $baseClass;
        }
        return $baseClass . "\n";
    }
    
    protected function filePath()
    {
        if ($this->classNamespace) {
            $namespaces = $this->classNamespace . '/';
        } else {
            $namespaces = '';
        }
        
        return 'app/helpers/' . $namespaces . $this->className . '.php';
    }
    
    protected function template()
    {
        return __DIR__ . '/templates/helper.php';
    }
    
    protected function configure()
    {
        $this
            ->setName('helper')
            ->setDescription('Generate a helper.')
            ->addArgument(
                'name',
                'required',
                'Name of the helper (e.g. Post, namespaced: Admin/User).'
            );
    }
}
