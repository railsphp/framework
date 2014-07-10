<?php
namespace Rails\Toolbox;

use Composer\Script\Event;

class AppRenamer
{
    public static function rename(Event $event)
    {
        $io        = $event->getIO();
        $question  = "Enter a name for the application (e.g. MyApp): ";
        $validator = function($name) {
            $regex = '/^[a-z0-9]+$/i';
            if (preg_match($regex, $name)) {
                return $name;
            }
            throw new InvalidArgumentException(
                "Name must contain only letters and numbers"
            );
        };
        $appName = $io->askAndValidate($question, $validator, true);
        
        $appRoot = $_SERVER['PWD'];
        
        $files = [
            $appRoot . '/config/application.php',
            $appRoot . '/config/boot.php'
        ];
        
        foreach ($files as $file) {
            $contents = file_get_contents($file);
            $contents = str_replace('NewApp', $appName);
            file_put_contents($contents, $file);
        }
        
        $io->write("Application renamed.");
    }
}
