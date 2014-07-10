<?php
namespace Rails\ActionView;

use Everzet\Jade\Jade as Base;

/**
 * Extends Everzet's Jade in order to improve the creation
 * of the cache key.
 */
class Jade extends Base
{
    /**
     * @var string
     * @see computeCacheKey()
     */
    protected $cacheKey;
    
    /**
     * Pre-computes the cache key out of the template path and the
     * root path. If the root path is found as part of the template
     * path, the URL-encoded tpl path without the root path will be the
     * key. Otherwise, the key will be template path hashed with crc32.
     * This method has to be called before `cache()`.
     *
     * @return string
     */
    public function computeCacheKey($templatePath, $rootPath = null)
    {
        if ($rootPath && strpos($templatePath, $rootPath) === 0) {
            $this->cacheKey = urlencode(substr($templatePath, strlen($rootPath) + 1));
        } else {
            $this->cacheKey = crc32($templatePath);
        }
    }
    
    /**
     * Returns $cacheKey as the cache key, so `computeCacheKey()` has
     * to be called before `cache()`.
     *
     * @return string
     */
    protected function getInputCacheKey($input)
    {
        if (is_file($input)) {
            return $this->cacheKey;
        } else {
            # If $input is not a file, let the parent throw the exception.
            return parent::getInputCacheKey($input);
        }
    }
}
