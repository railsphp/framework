<?php
namespace Rails\Toolbox;

use Composer\Script\Event;

class AppRenamer
{
    public static function renameComposer(Event $event)
    {
        $io       = $event->getIO();
        $question = "Enter a name for the application (e.g. MyApp): ";
        $appName  = $io->askAndValidate($question, 'Rails\Toolbox\AppRenamer::validateName', true);
        $appRoot  = $_SERVER['PWD'];
        
        self::rename($appName, $appRoot);
        
        $io->write("Application renamed.");
    }
    
    public static function rename($name, $railsRoot)
    {
        self::validateName($name);
        self::renameFiles($name, $railsRoot);
    }
    
    public static function validateName($name)
    {
        $regex = '/^[a-z0-9]+$/i';
        
        if (preg_match($regex, $name)) {
            return true;
        }
        throw new \InvalidArgumentException(
            "Name must contain only letters and numbers."
        );
    }
    
    protected static function renameFiles($name, $railsRoot)
    {
        $files = [
            $railsRoot . '/config/application.php',
            $railsRoot . '/config/boot.php'
        ];
        
        foreach ($files as $file) {
            $contents = file_get_contents($file);
            $contents = str_replace('NewApp', $name, $contents);
            file_put_contents($file, $contents);
        }
    }
}
