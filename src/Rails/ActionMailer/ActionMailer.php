<?php
namespace Rails\ActionMailer;

use Zend\Mail;
use Rails;

abstract class ActionMailer
{
    protected static $transport;
    
    public static function transport()
    {
        if (!self::$transport) {
            self::setTransport(self::getDefaultTransport());
        }
        return self::$transport;
    }
    
    public static function setTransport(Mail\Transport\TransportInterface $transport)
    {
        self::$transport = $transport;
    }
    
    public static function filenameGenerator()
    {
        return 'action_mailer_' . $_SERVER['REQUEST_TIME'] . '_' . mt_rand() . '.tmp';
    }
    
    protected static function getDefaultTransport()
    {
        $config = Rails::application()->config()['action_mailer'];
        
        if (!$config['delivery_method']) {
            throw new Exception\RuntimeException(
                "Delivery method for ActionMailer not set"
            );
        }
        
        switch ($config['delivery_method']) {
            /**
             * Rails -> Zend options:
             * address          -> host
             * port             -> port
             * domain           -> name
             * authentication   -> connection_class
             * user_name        -> connection_config[username]
             * password         -> connection_config[password]
             * enable_starttls_auto (true)  -> connection_config[ssl] => 'tls' (requires openssl extension)
             * enable_starttls_auto (false) -> connection_config[ssl] => null
             *
             * Regarding `authentication`, ZF2 adds the `smtp` option and
             * the `cram_md5` option is called `crammd5`.
             *
             * For convenience, the options under `connection_config` should be
             * set
             */
            case 'smtp':
                $defaultConfig = [
                    'host'              => '127.0.0.1',
                    'name'              => 'localhost',
                    'port'              => 25,
                    'connection_class'  => 'login'
                ];
                
                $defaultConnConfig =  [
                    'username'  => '',
                    'password'  => '',
                    'ssl'       => 'tls'
                ];
                
                $connConfig = array_merge(
                    $defaultConnConfig,
                    $config['smtp_settings']->delete('connection_config')->toArray()
                );
                
                $smtp = array_merge($defaultConfig, $config['smtp_settings']->toArray());
                
                
                $smtp['connection_config'] = $connConfig;
                
                $options    = new Mail\Transport\SmtpOptions($smtp);
                $transport  = new Mail\Transport\Smtp();
                $transport->setOptions($options);
                break;
            
            /**
             * Rails -> Zend options
             * location       -> path
             * name_generator -> callback
             */
            case 'file':
                $fileConfig = $config['file_settings'];
                $options    = [];
                
                if ($fileConfig->path === null) {
                    $dir = Rails::root() . '/tmp/mail';
                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }
                    $fileConfig->path = $dir;
                }
                
                $options['path'] = $fileConfig->path;
                
                if ($fileConfig->name_generator === null) {
                    $options['callback'] = 'Rails\ActionMailer\ActionMailer::filenameGenerator';
                } else {
                    $options['callback'] = $fileConfig->name_generator;
                }
                
                $fileOptions = new Mail\Transport\FileOptions($options);
                $transport   = new Mail\Transport\File();
                $transport->setOptions($fileOptions);
                break;
            
            case 'sendmail':
                $transport = new Mail\Transport\Sendmail();
                break;
            
            case ($config['delivery_method'] instanceof Closure):
                $transport = $config['delivery_method']();
                break;
            
            default:
                throw new Exception\RuntimeException(
                    sprintf("Unknown deilvery method %s", $config['delivery_method'])
                );
                break;
        }
        
        return $transport;
    }
}
