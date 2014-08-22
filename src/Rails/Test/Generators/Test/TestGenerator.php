<?php
namespace Rails\Test\Generators\Test;

use Rails\Generator\Generator;

class TestGenerator extends Generator
{
    protected static $VALID_TEST_TYPES = [
        'controller',
        'model',
        'helper',
        'mailer',
    ];
    
    protected $classNamespace;
    
    protected $className;
    
    protected $testType;
    
    public function defineTestType()
    {
        $testType = strtolower($this->arg('type'));
        
        if (!in_array($testType, self::$VALID_TEST_TYPES)) {
            throw new \InvalidArgumentException(sprintf(
                "Invalid test type '%s'",
                $testType
            ));
        }
        
        $this->testType = $testType;
    }
    
    public function extractNamespace()
    {
        if (strpos($this->arg('name'), '/') !== false) {
            $this->classNamespace = substr($this->arg('name'), 0, strrpos($this->arg('name'), '/'));
            $this->className = substr($this->arg('name'), strrpos($this->arg('name'), '/') + 1);
        } else {
            $this->className = $this->arg('name');
        }
        
        switch ($this->testType) {
            case 'controller':
            case 'mailer':
            case 'helper':
                $this->className .= ucfirst($this->testType);
                break;
        }
        $this->className .= 'Test';
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
        switch ($this->testType) {
            case 'controller':
                $baseClass = 'Rails\ActionController\Test\TestCase';
                break;
            
            default:
                $baseClass = 'Rails\Test\TestCase';
                break;
        }
        
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
        
        switch ($this->testType) {
            default:
                $testFolder = $this->testType . 's';
                break;
        }
        
        return 'test/' . $testFolder . '/' . $namespaces . $this->className . '.php';
    }
    
    protected function template()
    {
        return __DIR__ . '/templates/test.php';
    }
    
    protected function configure()
    {
        $this
            ->setName('test:test')
            ->setDescription('Generate a test file')
            ->addArgument(
                'type',
                'required',
                'Type of test (controller, model, mailer, helper).'
            )
            ->addArgument(
                'name',
                'required',
                'Name of the test (e.g. Posts, namespaced: Admin/Users).'
            );
    }
}
