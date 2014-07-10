<?php
namespace Rails\ActionView\Helper\Methods;

trait SanitizeTrait
{
    public function h($string, $flags = ENT_COMPAT, $charset = 'utf-8')
    {
        return htmlspecialchars($string, $flags, $charset);
    }
    
    public function stripTags($string)
    {
        return strip_tags($string);
    }
}
