<?php
namespace Rails\I18n;

use DirectoryIterator;
use Rails\Yaml\Parser as Yaml;

class Loader
{
    /**
     * Full paths to directories were translation files
     * are located, without trailing slash.
     */
    protected $paths            = [];
    
    protected $availableLocales = [];
    
    protected $loadedLocales    = [];
    
    protected $files            = [];
    
    protected $filesCached      = false;
    
    public function addPath($path)
    {
        if (!in_array($path, $this->paths)) {
            $this->paths[] = $path;
        }
    }
    
    public function addPaths(array $paths)
    {
        $this->paths = array_unique(array_merge($this->paths, $paths));
    }
    
    public function paths()
    {
        return $this->paths;
    }
    
    public function loadTranslations($locale)
    {
        if (in_array($locale, $this->loadedLocales)) {
            return true;
        }
        
        $this->cacheFiles();
        
        if (!isset($this->files[$locale])) {
            return false;
        }
        
        $tr = [];
        
        foreach ($this->files[$locale] as $file) {
            $ext    = pathinfo($file, PATHINFO_EXTENSION);
            $fileTr = [];
            
            if ($ext == 'php') {
                $fileTr = require $file;
                
                if (!is_array($fileTr)) {
                    throw new Exception\RuntimeException(
                        sprintf("Translation file '%s' must return array", $file)
                    );
                }
            } elseif ($ext == 'yml') {
                $fileTr = Yaml::readFile($file) ?: [];
            } # Ignore other extensions.
            
            if ($fileTr) {
                $tr = array_merge_recursive($tr, $fileTr);
            }
        }
        $this->loadedLocales[] = $locale;
        
        return $tr;
    }
    
    public function availableLocales()
    {
        $this->cacheFiles();
        return array_keys($this->files);
    }
    
    protected function cacheFiles()
    {
        if ($this->filesCached) {
            return;
        }
        
        $files = [];
        foreach ($this->paths as $path) {
            $dir = new DirectoryIterator($path);
            
            foreach ($dir as $finfo) {
                if (!$finfo->isFile()) {
                    continue;
                }
                
                $locale = pathinfo($finfo->getRealPath(), PATHINFO_FILENAME);
                
                if (!isset($files[$locale])) {
                    $files[$locale] = [];
                }
                
                $files[$locale][] = $finfo->getRealPath();
            }
        }
        $this->files = $files;
        $this->filesCached = true;
    }
}
