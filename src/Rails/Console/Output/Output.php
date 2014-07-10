<?php
namespace Rails\Console\Output;

use Symfony\Component\Console\Output\ConsoleOutput as Base;

class ConsoleOutput extends Base
{
    public function put($text = '', $options = null, $type = self::OUTPUT_NORMAL)
    {
        
    }
    
    public function line($text = '', $options = null, $type = self::OUTPUT_NORMAL)
    {
    }
    
    public function confirm()
    {
    }
    
    public function key()
    {
    }
    
    public function input()
    {
    }
    
    public function number()
    {
    }
    
    public function select()
    {
    }
    
    public function terminate()
    {
    }
}
