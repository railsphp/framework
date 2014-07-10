<?php
namespace Rails\ActionController\Generators\Controller;

use Rails\Generator\Generator;

class ControllerGenerator extends Generator
{
    protected $classNamespace;
    
    protected $className;
    
    public function extractNamespace()
    {
        if (strpos($this->arg('name'), '.') !== false) {
            $this->classNamespace = substr($this->arg('name'), 0, strrpos($this->arg('name'), '.'));
            $this->className = substr($this->arg('name'), strrpos($this->arg('name'), '.') + 1);
        } else {
            $this->className = $this->arg('name');
        }
    }
    
    public function writeFile()
    {
        $this->createFile();
    }
    
    protected function defineNamespace()
    {
        if ($this->classNamespace) {
            return 'namespace ' . str_replace('.', '\\', $this->classNamespace) . ";\n\n";
        }
    }
    
    protected function baseClass()
    {
        $baseClass = 'ApplicationController';
        if ($this->classNamespace) {
            $baseClass = '\\' . $baseClass;
        }
        return $baseClass . "\n";
    }
    
    protected function filePath()
    {
        if ($this->classNamespace) {
            $namespaces = str_replace('.', '/', $this->classNamespace) . '/';
        } else {
            $namespaces = '/';
        }
        return 'app/controllers/' . $namespaces . $this->className . 'Controller.php';
    }
    
    protected function template()
    {
        return __DIR__ . '/templates/controller.php';
    }
    
    protected function configure()
    {
        $this
            ->setName('controller')
            ->setDescription('Generate controller')
            ->addArgument(
                'name',
                'required',
                'Name of the controller (e.g. Users, namespaced: Admin.Posts).'
            );
    }
}
