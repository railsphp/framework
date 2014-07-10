<?php
namespace Rails\Assets\Compressor\Css;

use Minifier\CSSMin as CssMinifier;

class CssMin
{
    public static function compress($css)
    {
        $compressor = new CssMinifier();
        return $compressor->run($css);
    }
}
