<?php
namespace Rails\Assets\Compiler;

class PhpCompiler extends Base
{
    public static function compile($contents, $file)
    {
        ob_start();
        require $file->originalFilePath();
        return ob_get_clean();
    }
}
