<?php
namespace Rails\ActionView\Template;

use Rails\ActionView\ActionView;

class Template
{
    /**
     * @var Finder
     */
    protected $finder;
    
    /**
     * @var string
     */
    protected $locale;
    
    /**
     * @var string
     */
    protected $format;
    
    /**
     * @var string
     */
    protected $prefix;
    
    /**
     * @var string
     */
    protected $handler;
    
    /**
     * Full path to template file.
     * @var string
     */
    protected $filePath;
    
    /**
     * @var string
     */
    protected $cacheKey;
    
    /**
     * @var bool
     */
    protected $isPartial;
    
    public function __construct(
        Finder $finder,
        $filePath,
        $prefix,
        $locale,
        $format,
        $handler,
        $isPartial,
        $cacheKey
    ) {
        $this->finder   = $finder;
        $this->locale   = $locale;
        $this->format   = $format;
        $this->prefix   = $prefix;
        $this->handler  = $handler;
        $this->filePath = $filePath;
        $this->cacheKey = $cacheKey;
    }
    
    public function finder()
    {
        return $this->finder;
    }
    
    public function locale()
    {
        return $this->locale;
    }
    
    public function format()
    {
        return $this->format;
    }
    
    public function prefix()
    {
        return $this->prefix;
    }
    
    public function handler()
    {
        return $this->handler;
    }
    
    public function filePath()
    {
        return $this->filePath;
    }
    
    public function cacheKey()
    {
        return $this->cacheKey;
    }
    
    public function isPartial()
    {
        return $this->isPartial;
    }
}
