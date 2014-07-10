<?php
namespace Rails\Railtie;

use Rails\ServiceManager\ServiceLocatorAwareTrait;

class ConfigurableRailtieManager
{
    use ServiceLocatorAwareTrait;
    
    protected static $instance;
    
    protected $railtiesConfig = [];
    
    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function configureRailtie($className)
    {
        if (!isset($this->railtiesConfig[$className])) {
            $config = $this->initConfig($className);
            $this->railtiesConfig[$className] = $config;
        }
        return $this->railtiesConfig[$className];
    }
    
    protected function initConfig($className)
    {
        $config    = $this->getService('rails.config');
        
        $railtie   = $this->getRailtieClass($className);
        $namespace = $railtie::configNamespace();
        
        $presetConfig = $config->delete($namespace);
        
        $railtie::configure($config);
        
        if ($presetConfig) {
            $config->get($namespace)->merge($presetConfig->toArray());
        }
        
        return $config->get($namespace);
    }
    
    protected function getRailtieClass($className)
    {
        return
            substr(
                $className,
                0,
                strrpos($className, '\\')
            ) . '\Railtie';
    }
}
