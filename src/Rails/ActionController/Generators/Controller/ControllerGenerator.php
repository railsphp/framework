<?php
namespace Rails\ActionController\Generators\Controller;

use Rails\Generator\Generator;

class ControllerGenerator extends Generator
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
    }
    
    public function writeFile()
    {
        $this->createGenerator();
    }
    
    public function createViewDirectory()
    {
        $folderName = $this->app->getService('inflector')->underscore($this->className);
        $subDir = strtolower($this->classNamespace);
        if ($subDir) {
            $folderName = $subDir . '/' . $folderName;
        }
        $directory = $this->app->config()['paths']['root']->expand('app', 'views', $folderName);
        $this->createDirectory($directory);
    }
    
    public function createHelper()
    {
        $params = [$this->arg('name')];
        $this->invokeTask('helper', $params);
    }
    
    public function createTestFile()
    {
        $params = ['controller', $this->arg('name')];
        $this->invokeTask('test:test', $params);
    }
    
    protected function defineNamespace()
    {
        if ($this->classNamespace) {
            return 'namespace ' . str_replace('/', '\\', $this->classNamespace) . ";\n\n";
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
            $namespaces = $this->classNamespace . '/';
        } else {
            $namespaces = '';
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
                'Name of the controller (e.g. Users, namespaced: Admin/Posts).'
            );
    }
}
