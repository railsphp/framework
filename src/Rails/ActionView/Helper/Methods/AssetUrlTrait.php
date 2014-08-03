<?php
namespace Rails\ActionView\Helper\Methods;

use Rails\Assets\Manifest;

trait AssetUrlTrait
{
    protected $manifest;
    
    /**
     * URL path to asset.
     * Available options:
     * * assetDir: Directory used as fallback if the asset file isn't found.
     *             Must not contain leading nor trailing slashes.
     */
    public function assetPath($fileName, array $options = [])
    {
        if ($this->isRelativePath($fileName)) {
            if (substr($fileName, 0, 1) == '/') {
                $path = $fileName;
            } else {
                $path = '';
                
                if ($this->config['serve_static_assets']) {
                    $path = $this->getStaticAssetUrl($fileName);
                }
                
                if (!$path) {
                    $file = $this->getService('assets')->findFile($fileName);
                    if (!$file) {
                        $path = $this->defaultPath($fileName, $options);
                    } else {
                        $path = $file->url();
                    }
                }
            }
            
            return $this->properPath($path, $options);
        } else {
            return $fileName;
        }
    }
    
    /**
     * Full URL to asset.
     */
    public function assetUrl($fileName, array $options = [])
    {
        return $this->assetPath($fileName, array_merge($options, ['protocol' => 'request']));
    }
    
    protected function properPath($path, array $options)
    {
        $properPath = $this->computeAssetHost($options) . $this->routeSet->pathFor('base') . $path;
        if (!empty($options['body'])) {
            $properPath .= '?body=1';
        }
        return $properPath;
    }
    
    protected function computeAssetHost(array $options)
    {
        if (isset($options['protocol'])) {
            $host = $this->getService('assets')->host() ?: $this->request()->host();
            
            if (substr($host, 0, 1) == 'h') {
                return $host;
            }
            
            switch ($options['protocol']) {
                case 'relative':
                    return '//' . $host;
                
                case 'request':
                    return $this->request()->protocol() . $host;
                
                default:
                    return $options['protocol'] . '://' . $host;
            }
        }
        
        return '';
    }
    
    protected function defaultPath($file, array $options)
    {
        if (isset($options['assetDir'])) {
            return '/' . $options['assetDir'] . '/' . $file;
        } else {
            return $file;
        }
    }
    
    protected function isRelativePath($path)
    {
        return substr($path, 0, 2) != '//' && substr($path, 0, 4) != 'http';
    }
    
    /**
     * @return string|null
     */
    protected function getStaticAssetUrl($fileName)
    {
        if (!$this->manifest) {
            $assets    = $this->getService('assets');
            $assetsDir = $this->config['paths']['public_path']->expand(
                substr($assets->prefix(), 1)
            );
            
            $this->manifest = new Manifest(
                $assets,
                $assetsDir
            );
        }
        
        $contents = $this->manifest->contents();
        if (isset($contents['assets'][$fileName])) {
            return $this->config['assets']['prefix'] . '/' . $contents['assets'][$fileName];
        }
        return null;
    }
}
