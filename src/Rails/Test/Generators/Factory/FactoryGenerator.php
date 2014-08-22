<?php
namespace Rails\Test\Generators\Factory;

use Rails\Generator\Generator;

class FactoryGenerator extends Generator
{
    protected $subPath;
    
    protected $factoryName;
    
    public function extractNamespace()
    {
        $name = $this->arg('name');
        
        if (strpos($name, '/') !== false) {
            $this->subPath      = strtolower(substr($name, 0, strrpos($name, '/')));
            $this->factoryName  = substr($name, strrpos($name, '/') + 1);
        } else {
            $this->factoryName  = $name;
        }
    }
    
    public function writeFile()
    {
        $this->createGenerator();
    }
    
    protected function filePath()
    {
        if ($this->subPath) {
            $subPath = $this->subPath . '/';
        } else {
            $subPath = '';
        }
        return 'test/factories/' . $subPath . $this->factoryName . '.php';
    }
    
    protected function template()
    {
        return __DIR__ . '/templates/factory.php';
    }
    
    protected function configure()
    {
        $this
            ->setName('test:factory')
            ->setDescription('Generate a factory file')
            ->addArgument(
                'name',
                'required',
                'Name of the factory (e.g. post, admin/user).'
            );
    }
}
