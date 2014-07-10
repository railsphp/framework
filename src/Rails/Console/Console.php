<?php
namespace Rails\Console;

use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Console
{
    protected $stderr;
    
    public function __construct()
    {
        $this->stderr = new StreamOutput(
            fopen('php://stderr', 'w'),
            StreamOutput::VERBOSITY_NORMAL,
            null,
            null
        );
    }
    
    public function __call($method, $params)
    {
        return $this->zend($method, $params);
    }
    
    public function zend($method, $params)
    {
        call_user_func_array([$this->instance(), $method], $params);
    }
    
    /**
     * Alias for writeLine
     */
    public function put($text = "", $color = null, $bgColor = null)
    {
        $this->zend('writeLine', [$text, $this->properColor($color), $this->properColor($bgColor)]);
    }
    
    /**
     * Ask the user to confirm something.
     */
    public function confirm()
    {
        return call_user_func_array('Zend\Console\Prompt\Confirm::prompt', func_get_args());
    }
    
    /**
     * Ask the user to hit a key.
     */
    public function key()
    {
        return call_user_func_array('Zend\Console\Prompt\Char::prompt', func_get_args());
    }
    
    /**
     * Ask the user for a text.
     */
    public function input()
    {
        return call_user_func_array('Zend\Console\Prompt\Line::prompt', func_get_args());
    }
    
    public function number()
    {
        return call_user_func_array('Zend\Console\Prompt\Number::prompt', func_get_args());
    }
    
    public function select()
    {
        return call_user_func_array('Zend\Console\Prompt\Select::prompt', func_get_args());
    }
    
    /**
     * Returns Zend console instance.
     */
    public function instance()
    {
        return \Zend\Console\Console::getInstance();
    }
    
    public function getErrorOutput()
    {
        return $this->stderr;
    }
    
    public function setErrorOutput(OutputInterface $error)
    {
        $this->stderr = $error;
    }
    
    public function terminate($text = "", $color = null, $bgColor = null)
    {
        $this->zend('writeLine', [$text, $this->properColor($color), $this->properColor($bgColor)]);
        exit;
    }
    
    protected function properColor($color)
    {
        if (preg_match('/^[\s\w]+$/', $color)) {
            $const = 'Zend\Console\ColorInterface::' . 
                strtoupper(str_replace(' ', '_', $color));
            
            if (defined($const)) {
                return constant($const);
            } else {
                return null;
            }
        }
        return $color;
    }
}
