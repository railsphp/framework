<?php
namespace Rails\Assets\Compiler;

use CoffeeScript;

class CoffeeCompiler extends Base
{
    public static function compile($contents, $file)
    {
        return CoffeeScript\Compiler::compile(
            $contents,
            [
                'filename' => $file->originalFilePath(),
                'bare'     => true
            ]
        );
    }
}
