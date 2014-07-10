<?php
namespace Rails\Assets\Compiler;

class Base
{
    const FILE_JOINER = '';
    
    public static function fileJoiner()
    {
        return static::FILE_JOINER . "\n";
    }
}
