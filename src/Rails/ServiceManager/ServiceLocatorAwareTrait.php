<?php
namespace Rails\ServiceManager;

trait ServiceLocatorAwareTrait
{
    public static function services()
    {
        return \Rails::serviceManager();
    }
    
    public static function getService($name)
    {
        return \Rails::serviceManager()->get($name);
    }
}
