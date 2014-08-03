<?php
namespace Rails\ActionView\Helper\Methods;

use Closure;
use Rails\Assets\Processor\Processor as AssetProcessor;

trait AssetTagTrait
{
    public function javascriptIncludeTag($sources, array $options = [])
    {
        $cacheKey = array_merge(['asset-tag-js'], (array)$sources, $options);
        $tags = $this->getCachedAssetTags($cacheKey);
        
        if (!$tags) {
            $tags = $this->buildAssetTag('js', function($path, $options) {
                return $this->contentTag(
                    'script',
                    '',
                    array_merge(['src' => $path], $options)
                );
            }, $sources, $options);
            
            $this->cacheAssetTags($cacheKey, $tags);
        }
        
        return $tags;
    }
    
    public function stylesheetLinkTag($sources, array $options = [])
    {
        $cacheKey = array_merge(['asset-tag-css'], (array)$sources, $options);
        $tags = $this->getCachedAssetTags($cacheKey);
        
        if (!$tags) {
            $tags = $this->buildAssetTag('css', function($path, $options) {
                return $this->tag(
                    'link',
                    array_merge(
                        [
                            'rel'   => 'stylesheet',
                            'type'  => 'text/css',
                            'media' => 'screen',
                            'href'  => $path
                        ],
                        $options
                    )
                );
            }, $sources, $options);
            
            $this->cacheAssetTags($cacheKey, $tags);
        }
        
        return $tags;
    }
    
    /**
     * If passing a $type other than 'rss' or 'atom', the tagOption
     * 'type' must be specified.
     * Valid $tagOptions:
     * - rel: defaults to 'alternate'.
     * - type: defaults to 'application/rss+xml' for RSS and 'application/atom+xml' for Atom.
     * - title: defaults $type uppercased.
     */
    public function autoDiscoveryLinkTag($type = 'rss', $urlOptions = '', array $tagOptions = [])
    {
        if ($type != 'rss' && $type != 'atom') {
            if (empty($tagOptions['type'])) {
                throw new Exception\BadMethodCallException(
                    sprintf(
                        "The 'type' \$tagOptions must be specified when passing a \$type other than rss and atom (%s)",
                        $type
                    )
                );
            }
        } elseif (empty($tagOptions['type'])) {
            if ($type = 'rss') {
                $tagOptions['type'] = 'application/rss+xml';
            } else {
                $tagOptions['type'] = 'application/atom+xml';
            }
        }
        
        if (empty($tagOptions['rel'])) {
            $tagOptions['rel'] = 'alternate';
        }
        if (empty($tagOptions['title'])) {
            $tagOptions['title'] = strtoupper($type);
        }
        
        return $this->tag(
            'link',
            [
                'rel'   => $tagOptions['rel'],
                'type'  => $tagOptions['type'],
                'title' => $tagOptions['title'],
                'href'  => $this->urlFor($urlOptions, ['onlyPath' => false]),
            ]
        );
    }
    
    public function faviconLinkTag($source = 'favicon.ico',  array $options = [])
    {
        return $this->tag(
            'link',
            array_merge([
                'rel'  => 'shortcut icon',
                'type' => 'image/x-icon',
                'href' => $this->assetPath($source, ['assetDir' => 'images'])
            ], $options)
        );
    }
    
    public function imageTag($source, array $options = [])
    {
        $options['src'] = $this->assetPath($source, ['assetDir' => 'images']);
        
        if (
            is_bool(strpos($options['src'], 'cid:')) &&
            is_bool(strpos($options['src'], 'data:'))
        ) {
            $options['alt'] = $this->imageAlt($options['src']);
        }
        
        $this->properTagSize($options);
        
        return $this->tag('img', $options);
    }
    
    public function imageAlt($src)
    {
        return $this->getService('inflector')->humanize(
            str_replace(['-', '_'], ' ', preg_replace('/-[a-f0-9]{32}\z/', '', pathinfo($src, PATHINFO_BASENAME)))
        );
    }
    
    public function videoTag($sources, array $options = [])
    {
        if (!empty($options['poster'])) {
            $options['poster'] = $this->assetPath($options['poster'], ['assetDir' => 'images']);
        }
        $this->properTagSize($options);
        return $this->multipleSourcesTag('video', $sources, $options);
    }
    
    public function audioTag($sources, array $options = [])
    {
        return $this->multipleSourcesTag('audio', $sources, $options);
    }
    
    protected function multipleSourcesTag($type, $sources, array $options)
    {
        $sources = (array)$sources;
        
        if (count($sources) > 1) {
            $contents = [];
            foreach ($sources as $source) {
                $contents[] = $this->tag('source', ['src' => $this->assetPath($source, ['assetDir' => $type . 's'])]);
            }
            return $this->contentTag($type, implode($contents), $options);
        } else {
            $options['src'] = $this->assetPath($sources, ['assetDir' => $type . 's']);
            return $this->contentTag($type, '', $options);
        }
    }
    
    protected function buildAssetTag($type, Closure $builder, $sources, array $options)
    {
        $sources = (array)$sources;
        $tags    = [];
        if (isset($options['protocol'])) {
            $protocol = $options['protocol'];
            unset($options['protocol']);
        } else {
            $protocol = '';
        }
        
        if ($this->config['serve_static_assets']) {
            foreach ($sources as &$source) {
                $this->ensureAssetType($type, $source);
            }
            
            // if ($this->config['use_cache']) {
                // $key = 'asset-tags-' . crc32(implode($sources));
                // if ($tags = $this->getService('rails.cache')->read($key)) {
                    // return $tags;
                // }
                // $tags = [];
            // }
            
            foreach ($sources as $source) {
                $this->appendAssetTag($source, $type, $builder, $options, false, $tags);
            }
            
            // if ($this->config['use_cache']) {
                // $this->getService('rails.cache')->write($key, implode("\n", $tags));
            // }
        } else {
            if ($this->config['assets']['concat']) {
                foreach ($sources as $source) {
                    $this->ensureAssetType($type, $source);
                    $this->compileAssetFile($source);
                    $this->appendAssetTag($source, $type, $builder, $options, false, $tags);
                }
            } else {
                $assets    = $this->getService('assets');
                $processor = new AssetProcessor($assets);
                foreach ($sources as $source) {
                    $this->ensureAssetType($type, $source);
                    
                    $file = $assets->findFile($source);
                    
                    if (!$file) {
                        $this->compileAssetFile($source);
                        $this->appendAssetTag($source, $type, $builder, $options, true, $tags);
                        continue;
                    }
                    
                    $processor->listFiles($file);
                    foreach ($processor->files() as $subFile) {
                        $this->compileAssetFile($subFile->logicalPath());
                        $this->appendAssetTag($subFile->url(), $type, $builder, $options, true, $tags);
                    }
                }
            }
        }
        
        return implode("\n", $tags);
    }
    
    // protected function compileAndAppend($source, $type, Closure $builder, array $options, $body, array &$tags)
    // {
        // $this->compileAssetFile($source);
        
        // if ($type == 'css') {
            // $assetDir = 'stylesheets';
        // } else {
            // $assetDir = 'javascripts';
        // }
        
        // $url = $this->assetPath(
            // $source,
            // ['assetDir' => $assetDir, 'body' => $body]
        // );
        
        // $tags[] = $builder($url, $options);
    // }
    
    protected function appendAssetTag($source, $type, Closure $builder, array $options, $body, array &$tags)
    {
        if ($type == 'css') {
            $assetDir = 'stylesheets';
        } else {
            $assetDir = 'javascripts';
        }
        
        $url = $this->assetPath(
            $source,
            ['assetDir' => $assetDir, 'body' => $body]
        );
        
        $tags[] = $builder($url, $options);
    }
    
    protected function ensureAssetType($type, &$source)
    {
        if (substr($source, strlen($source) - (strlen($type) + 1)) != '.' . $type) {
            $source .= '.' . $type;
        }
    }
    
    protected function compileAssetFile($source)
    {
        $this->getService('assets')->compileFile($source);
    }
    
    protected function getCachedAssetTags($key)
    {
        if ($this->config['use_cache']) {
            return $this->getService('rails.cache')->read($key);
        }
    }
    
    protected function cacheAssetTags($key, $value)
    {
        if ($this->config['use_cache']) {
            $this->getService('rails.cache')->write($key, $value);
        }
    }
}
