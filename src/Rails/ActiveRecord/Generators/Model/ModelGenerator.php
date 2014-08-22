<?php
namespace Rails\ActiveRecord\Generators\Model;

use Symfony\Component\Console\Input\ArgvInput;
use Rails\Generator\Generator;
use Rails\ActiveRecord\Generators\Migration\MigrationGenerator;

class ModelGenerator extends Generator
{
    protected $classNamespace;
    
    protected $className;
    
    public function extractNamespace()
    {
        if (strpos($this->arg('name'), '/') !== false) {
            $this->classNamespace = substr($this->arg('name'), 0, strrpos($this->arg('name'), '/'));
            $this->className = substr($this->arg('name'), strrpos($this->arg('name'), '/') + 1);
        } else {
            $this->className = $this->arg('name');
        }
    }
    
    public function writeFile()
    {
        $this->createGenerator();
    }
    
    public function createMigration()
    {
        if ($this->opt('migration') === null) {
            $name = $this->app->getService('inflector')->pluralize($this->arg('name'))->underscore();
            $name = str_replace('/', '_', $name);
            $params = ['create_' . $name];
            $this->invokeTask('migration', $params);
        }
    }
    
    public function createTestFile()
    {
        $params = ['model', $this->arg('name')];
        $this->invokeTask('test:test', $params);
    }
    
    protected function defineNamespace()
    {
        if ($this->classNamespace) {
            return 'namespace ' . $this->classNamespace . ";\n\n";
        }
    }
    
    protected function baseClass()
    {
        $baseClass = 'Rails\ActiveRecord\Base';
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
        return 'app/models/' . $namespaces . $this->className . '.php';
    }
    
    protected function template()
    {
        return __DIR__ . '/templates/model.php';
    }
    
    protected function configure()
    {
        $this
            ->setName('model')
            ->setDescription('Generate model')
            ->addArgument(
                'name',
                'required',
                'Name of the model (e.g. User, Admin/Post)'
            )
            ->addOption(
                'migration',
                'm',
                'optional',
                'Skip migration'
            );
    }
}
