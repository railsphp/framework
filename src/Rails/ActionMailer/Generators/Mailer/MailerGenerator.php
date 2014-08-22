<?php
namespace Rails\ActionMailer\Generators\Mailer;

use Rails\Generator\Generator;

class MailerGenerator extends Generator
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
        $this->className .= 'Mailer';
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
        $baseClass = 'Rails\ActionMailer\Base';
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
        
        return 'app/mailers/' . $namespaces . $this->className . '.php';
    }
    
    protected function template()
    {
        return __DIR__ . '/templates/mailer.php';
    }
    
    protected function configure()
    {
        $this
            ->setName('mailer')
            ->setDescription('Generate a mailer.')
            ->addArgument(
                'name',
                'required',
                'Name of the mailer (e.g. NewPassword, namespaced: Admin/User).'
            );
    }
}
