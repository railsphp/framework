<?php
namespace Rails\Assets\File;

use Rails\Assets\Assets;

class Finder
{
    public function __construct(Assets $assets)
    {
        $this->assets = $assets;
    }
    
    public function find($filename, $paths = [], $findFirst = true)
    {
        if (!$paths) {
            $paths = $this->assets->paths();
        } elseif (!is_array($paths)) {
            $paths = [$paths];
        }
        
        $pinfo = pathinfo($filename);
        $path  = $pinfo['dirname'] == '.' ? '' : $pinfo['dirname'] . DIRECTORY_SEPARATOR;
        
        $filePattern = $path . $pinfo['basename'] . '*';
        
        if ($findFirst) {
            $file = false;
        } else {
            $files = [];
        }
        
        foreach ($paths as $assetRoot) {
            $pattern    = $assetRoot . DIRECTORY_SEPARATOR . $filePattern;
            $matched    = glob($pattern);
            
            if ($matched) {
                if ($findFirst) {
                    $file = $this->buildFileFromMatch($matched[0], $pinfo, $assetRoot);
                    break;
                } else {
                    foreach ($matched as $match) {
                        $files[] = $this->buildFileFromMatch($match, $pinfo, $assetRoot);
                    }
                }
            }
        }
        
        if ($findFirst) {
            return $file;
        } else {
            return $files;
        }
    }
    
    public function findAll(array $manifestFiles)
    {
        // $js  = '.js';
        // $css = '.css';
        
        // $filter = function($file) use ($js, $css, $manifestFiles) {
            // $name = $file->getBasename();
            // if (
                // is_int(strpos($name, $js))  ||
                // is_int(strpos($name, $css))
            // ) {
                // foreach ($manifestFiles as $manifestFile) {
                    // if (is_int(strpos($name, $manifestFile))) {
                        // return true;
                    // }
                // }
                // return false;
            // }
            // return true;
        // };
        
        $files = [];
        foreach ($this->assets->paths() as $path) {
            foreach (
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::CHILD_FIRST
                ) as $finfo
            ) {
                if ($finfo->isFile()) {
                    $name = $finfo->getBasename();
                    $fileName = '';
                    if (preg_match('/(.*?\.(js|css))[^\w]?/', $name)) {
                        $isManifest = false;
                        foreach ($manifestFiles as $manifestFile) {
                            if (is_int(strpos($name, $manifestFile))) {
                                $pos = strpos($finfo->getPathname(), $manifestFile);
                                $fileName = substr($finfo->getPathname(), 0, $pos + strlen($manifestFile));
                                $isManifest = true;
                                break;
                            }
                        }
                        if (!$isManifest) {
                            continue;
                        }
                    } else {
                    
                    }
                    if (!$fileName) {
                        $fileName = $finfo->getPathName();
                    }
                    $pinfo = pathinfo($fileName);
                    
                    $files[] = $this->buildFileFromMatch(
                        $finfo->getPathName(),
                        $pinfo,
                        $path
                    );
                }
            }
        }
        return $files;
    }
    
    protected function buildFileFromMatch($match, $pinfo, $assetRoot)
    {
        $extensions = $this->getExtensions(
            $pinfo['extension'],
            pathinfo($match, PATHINFO_BASENAME)
        );
        
        return new File(
            $this,
            $assetRoot,
            $pinfo['filename'],
            $extensions,
            $this->extractSubPaths($assetRoot, $match, $pinfo),
            realpath($match)
        );
    }
    
    protected function getExtensions($mainExt, $baseName)
    {
        $pos = strrpos($baseName, '.' . $mainExt);
        $exts = substr($baseName, $pos + 1);
        if ($exts) {
            return explode('.', $exts);
        }
        return [];
    }
    
    /**
     * @param string $filename
     * @param string|array $paths
     */
    public function findWithPrependedPaths($filename, $paths)
    {
        if (!is_array($paths)) {
            $paths = [$paths];
        }
        
        return $this->find(
            $filename,
            array_unique(array_merge($paths, $this->assets->paths()))
        );
    }
    
    public function assets()
    {
        return $this->assets;
    }
    
    protected function extractSubPaths($assetRoot, $filePath, array $pinfo)
    {
        $subPath = substr(
            $filePath,
            strlen($assetRoot),
            strlen($assetRoot) - strpos($filePath, '/' . $pinfo['basename'])
        );
        
        if ($subPath) {
            return explode('/', $subPath);
        } else {
            return [];
        }
    }
}
