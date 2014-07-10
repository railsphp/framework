<?php
namespace Rails\ActionMailer;

use stdClass;
use Rails;
use Rails\ActionView\Template\Assigns;
use Rails\ServiceManager\ServiceLocatorAwareTrait;

abstract class Base
{
    use ServiceLocatorAwareTrait;
    
    public $from;
    
    public $to;
    
    public $subject;
    
    public $templatePath;
    
    public $charset;
    
    public $textCharset;
    
    public $htmlCharset;
    
    public $templateName;
    
    public $attachments = [];
    
    public $calledMethod;
    
    public $headers = [];
    
    public $assigns;
    
    public static function mail($method, array $params = [], array $headers = [])
    {
        $className = get_called_class();
        $mailer = new $className();
        $mailer->calledMethod = $method;
        $mailer->headers = array_merge($mailer->headers, $headers);
        
        if (false !== call_user_func_array([$mailer, $method], $params)) {
            return $mailer->createMail();
        }
    }
    
    /**
     * init() may be overriden instead of __construct().
     */
    public function __construct()
    {
        $this->assigns = new Assigns();
        $this->assigns->setStrict(false);
        $this->setDefaults();
        $this->init();
    }
    
    public function __set($prop, $value)
    {
        $this->assigns->set($prop, $value);
    }
    
    public function __get($prop)
    {
        return $this->assigns->get($prop);
    }
    
    /**
     * Just a quicker way to add an attachment.
     */
    public function attachment($name, $content)
    {
        if (!is_string($content) && (!is_resource($content) || get_resource_type($content) != 'stream')) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    "Attachment content must be either string or stream, %s passed",
                    gettype($content)
                )
            );
        }
        
        $this->attachments[$name] = [
            'content' => $content
        ];
    }
    
    /**
     * Just a quicker way to add an inline attachment.
     */
    public function inlineAttachment($name, $content)
    {
        if (!is_string($content) && (!is_resource($content) || get_resource_type($content) != 'stream')) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    "Attachment content must be either string or stream, %s passed",
                    gettype($content)
                )
            );
        }
        
        $this->attachments[$name] = [
            'content' => $content,
            'inline'  => true
        ];
    }
    
    protected function init()
    {
    }
    
    protected function setDefaults()
    {
        $config = Rails::application()->config()['action_mailer'];
        if ($config['defaults']->any()) {
            foreach ($config['defaults'] as $key => $value) {
                $this->$key = $value;
            }
        }
    }
    
    protected function createMail()
    {
        if (!$this->templateName) {
            $this->templateName = $this->getService('inflector')->underscore($this->calledMethod)->toString();
        }
        
        if (!$this->templatePath) {
            $this->templatePath = $this->getService('inflector')->underscore(get_called_class())->toString();
        }
        
        return new Deliverer($this);
    }
}
