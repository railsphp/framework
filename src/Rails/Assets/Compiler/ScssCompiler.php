<?php
namespace Rails\Assets\Compiler;

use scssc;

class ScssCompiler
{
    public static function compile($contents, $file)
    {
        $scss = new scssc();
        return $scss->compile($contents);
    }
}
