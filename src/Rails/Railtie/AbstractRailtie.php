<?php
namespace Rails\Railtie;

use Rails\ServiceManager\ServiceLocatorAwareTrait;

abstract class AbstractRailtie
{
    use ServiceLocatorAwareTrait;
    
    public static function configure($config)
    {
    }
    
    public static function configNamespace()
    {
        return self::getService('inflector')->underscore(
            explode('\\', get_called_class())[1]
        )->toString();
    }
}
