<?php
namespace Rails\FactoryGirl;

use Faker;
use Rails\Toolbox\FileTools;
use Rails\Toolbox\ClassTools;

class FactoryGirl
{
    /**
     * @var Factories
     */
    protected $factories;
    
    /**
     * @var Sequences
     */
    protected $sequences;
    
    /**
     * @var Fixtures
     */
    protected $fixtures;
    
    /**
     * @var Faker\Generator
     */
    protected $faker;
    
    public function __construct(array $config)
    {
        $this->factories = new Factories();
        $this->sequences = new Sequences();
        $this->fixtures  = new Fixtures();
        
        if (isset($config['factoryPaths'])) {
            $this->includeFactories($config['factoryPaths']);
        }
    }
    
    public function setFaker(Faker\Generator $faker)
    {
        $this->faker = $faker;
    }
    
    public function faker()
    {
        if (!$this->faker) {
            $this->faker = Faker\Factory::create('');
        }
        return $this->faker;
    }
    
    public function setFactory($name, array $options, array $attributes = [])
    {
        if (!$attributes) {
            $attributes = $options;
            $options    = [];
        }
        
        if (isset($options['aliases'])) {
            $aliases = $options['aliases'];
            unset($options['aliases']);
        } else {
            $aliases = [];
        }
        
        $factory = new Factory($name, $options, $attributes);
        $this->factories->set($name, $factory);
        
        if ($aliases) {
            foreach ($aliases as $alias) {
                $this->factories->set($alias, $factory);
            }
        }
    }
    
    /**
     * Note that both $options and $attributes must be passed.
     */
    public function extendFactory($baseName, $name, array $options, array $attributes)
    {
        $base = $this->factories->get($baseName);
        
        if (!$base) {
            throw new Exception\RuntimeException(sprintf(
                "Factory '%s' doesn't exist",
                $baseName
            ));
        }
        
        $factory = $this->factories->get($baseName);
        $clone   = $factory->extend($name, $options, $attributes);
        $this->factories->set($name, $clone);
    }
    
    public function setSequence($name, $value)
    {
        $this->sequences->set($name, $value);
    }
    
    public function sequence($name)
    {
        return $this->sequences->getValue($name);
    }
    
    public function setFixture($namespace, $name, $value)
    {
        return $this->fixtures->set($namespace, $name, $value);
    }
    
    public function fixture($namespace, $name)
    {
        return $this->fixtures->get($namespace, $name);
    }
    
    public function build($factoryName, array $extraAttrs = [])
    {
        return $this->factories->get($factoryName)->build($extraAttrs);
    }
    
    public function create($factoryName, array $extraAttrs = [])
    {
        return $this->factories->get($factoryName)->create($extraAttrs);
    }
    
    public function includeFactories(array $paths)
    {
        $includer = ClassTools::generateFileIncluder($this);
        
        foreach ($paths as $path) {
            foreach (FileTools::listFilesRecursive($path) as $file) {
                $includer($file->getRealPath());
            }
        }
    }
}
