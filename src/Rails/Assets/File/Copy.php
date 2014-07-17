<?php
namespace Rails\Assets\File;

abstract class Copy extends File
{
    public static function getNeighbor(File $origin, $filename)
    {
        if (!self::isSameType($origin, $filename)) {
            return false;
        }
        
        $filename  = str_replace('\\', '/', $filename);
        $nameParts = explode(
            '.',
            pathinfo($filename, PATHINFO_BASENAME)
        );
        
        $subPaths  = explode('/', trim(
            substr($filename, strlen(
                pathinfo($origin->originalFilePath(), PATHINFO_DIRNAME)
            )),
            '/'
        ));
        array_pop($subPaths);
        
        $neighbor             = clone $origin;
        $neighbor->name       = array_shift($nameParts);
        $neighbor->extensions = $nameParts;
        $neighbor->subPaths   = $subPaths;
        $neighbor->originalFilePath = $filename;
        return $neighbor;
    }
    
    public static function isSameType(File $origin, $filename)
    {
        $extensions = explode(
            '.',
            pathinfo($filename, PATHINFO_BASENAME)
        );
        array_shift($extensions);
        return $origin->type() == array_shift($extensions);
    }
}
