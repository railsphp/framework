<?php
namespace Rails\ActionView\Template;

use DirectoryIterator;
use Rails\ActionView\ActionView;

class Finder
{
    protected $actionView;
    
    protected $cache;
    
    public function __construct(ActionView $actionView)
    {
        $this->actionView = $actionView;
    }
    
    // public function setCache($cache)
    // {
        // $this->cache = $cache;
    // }
    
    public function actionView()
    {
        return $this->actionView;
    }
    
    public function findTemplate(
        $name,
        $isPartial,
        array $prefixes,
        array $locale,
        array $formats,
        array $handlers
    ) {
        $cacheKey = $this->generateCacheKey($name, $isPartial, $prefixes, $locale, $formats, $handlers);
        $useCache = $this->actionView->getService('rails.config')['use_cache'];
        
        if ($useCache) {
            # TODO: use cache and must return the path if found.
            if ($data = $this->actionView->getService('rails.cache')->read($cacheKey)) {
                return new Template(
                    $this,
                    $data[0],
                    $data[1],
                    $data[2],
                    $data[3],
                    $data[4],
                    $isPartial,
                    $cacheKey
                );
            }
        }
    
        if ($isPartial) {
            $originalName = $name;
            $name = $this->partialName($name);
        }
        
        $pattern = $this->generatePattern($name, false, $locale, $formats, $handlers);
        
        $tplData = null;
        
        foreach ($this->actionView->lookupContext()->paths as $templatePath) {
            if ($prefixes) {
                foreach ($prefixes as $prefix) {
                    $prefixedName = $prefix . '/' . $name;
                    $properPath = $templatePath . DIRECTORY_SEPARATOR . $prefixedName;
                    if ($tplData = $this->find($properPath, $pattern)) {
                        break 2;
                    }
                }
            } else {
                if ($tplData = $this->find($templatePath . '/' . $name, $pattern)) {
                    if ($isPartial) {
                        $prefix = $this->extractPrefix($originalName);
                    } else {
                        $prefix = $this->extractPrefix($name);
                    }
                    break;
                }
            }
        }
        
        if ($tplData) {
            if ($useCache) {
                $this->actionView->getService('rails.cache')->write(
                    $cacheKey,
                    [
                        $tplData[0],
                        $prefix,
                        $tplData[1][1],
                        $tplData[1][2],
                        $tplData[1][3]
                    ]
                );
            }
            
            return new Template(
                $this,
                $tplData[0],
                $prefix,
                $tplData[1][1],
                $tplData[1][2],
                $tplData[1][3],
                $isPartial,
                $cacheKey
            );
        } else {
            return null;
        }
    }
    
    protected function partialName($name)
    {
        if ($pos = strrpos($name, '/')) {
            return substr_replace($name, '/_', $pos, 1);
        }
        return '_' . $name;
    }
    
    protected function find($path, $pattern)
    {
        $files = glob($path . '*');
        
        if ($files) {
            foreach ($files as $file) {
                if (preg_match($pattern, $file, $m)) {
                    return [$file, $m];
                }
            }
        }
    }
    
    protected function generateCacheKey($name, $isPartial, array $prefixes, array $locale, array $formats, array $handlers)
    {
        $key = '';
        if ($isPartial) {
            $key .= '_';
        }
        $key .= $name;
        $key .= '.' . implode(',', $prefixes);
        $key .= '.' . implode(',', $locale);
        $key .= '.' . implode(',', $formats);
        $key .= '.' . implode(',', $handlers);
        
        return 'tpl-' . crc32($key);
    }
    
    protected function extractPrefix($name)
    {
        if ($pos = strpos($name, '/')) {
            return substr($name, 0, $pos);
        }
        return '';
    }
    
    protected function generatePattern($name, $isPartial, array $locale, array $formats, array $handlers)
    {
        if ($isPartial) {
            $path = '_' . $name;
        } else {
            $path = $name;
        }
        
        if ($locale) {
            $path .= '(?:\.(' . implode('|', $locale) . '))?';
        } else {
            $path .= '()';
        }
        if ($formats) {
            $path .= '(?:\.(' . implode('|', $formats) . '))?';
        } else {
            $path .= '()';
        }
        if ($handlers) {
            $path .= '(?:\.(' . implode('|', $handlers) . '))';
        } else {
            $path .= '()';
        }
        
        return '~' . $path . '$~';
    }
}
