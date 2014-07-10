<?php
namespace Rails\Toolbox;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;

abstract class FileTools
{
    /**
     * Deletes all files and directories recursively from a directory.
     * Note that existance of the dir must be previously checked.
     * The directory isn't deleted.
     * SO.com:1407338
     */
    public static function emptyDirRecursive($dirPath)
    {
        foreach (
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dirPath, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            ) as $path
        ) {
            if ($path->isFile()) {
                unlink($path->getPathname());
            } else {
                rmdir($path->getPathname());
            }
        }
    }
    
    /**
     * Iterates over a tree and takes all files. Files can be
     * filtered with a function that will receive a SplFileInfo
     * object as parameter, and must return `true` for the file to
     * be included in the results.
     * Finally, returns an array of SplFileInfo objects.
     *
     * @return array
     */
    public static function listFilesRecursive($dirPath, \Closure $filter = null)
    {
        $files = [];
        foreach (
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dirPath, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            ) as $finfo
        ) {
            if ($finfo->isFile()) {
                if ($filter) {
                    if ($filter($finfo)) {
                        $files[] = $finfo;
                    }
                } else {
                    $files[] = $finfo;
                }
            }
        }
        return $files;
    }
    
    /**
     * Lists all directories and subdirectories found in a path.
     */
    public static function listDirsRecusrive($root)
    {
        $dirs = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $path) {
            if ($path->isDir()) {
                $dirs[] = $path->getPathname();
            }
        }
        return $dirs;
    }
    
    /**
     * Search for all files matching a pattern within a directory and sub directories.
     */
    public static function searchFile($root, $pattern = '*', $flags = 0)
    {
        $dirs = self::listDirs($root);
        array_unshift($dirs, $root);
        
        $foundFiles = [];
        
        if ($pattern instanceof \Closure) {
            $closure = $pattern;
            $pattern = '*';
        } else {
            $closure = null;
        }
        
        foreach ($dirs as $dir) {
            $filePatt = $dir . '/' . $pattern;
            $files = glob($filePatt, $flags);
            
            if ($closure) {
                foreach ($files as $file) {
                    if ($closure($file)) {
                        $foundFiles[] = $file;
                    }
                }
            } else {
                if ($files) {
                    $foundFiles = array_merge($foundFiles, $files);
                }
            }
        }
        
        return $foundFiles;
    }
    
    /**
     * File modification time.
     * Found at PHP.net
     * filemtime() returns invalid date on Windows, this function fixes that.
     */
    public static function modTime($path) 
    { 
        $time       = filemtime($path);
        $isDst      = (date('I', $time) == 1);
        $system_dst = (date('I') == 1);
        $adjustment = 0;
        
        if ($isDst == false && $system_dst == true) {
            $adjustment = 3600;
        } elseif ($isDst == true && $system_dst == false) {
            $adjustment = -3600;
        } else {
            $adjustment = 0;
        }

        return ($time + $adjustment);
    }
}