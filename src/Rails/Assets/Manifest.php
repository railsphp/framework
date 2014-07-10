<?php
namespace Rails\Assets;

class Manifest
{
    /**
     * Directory where manifest file resides, i.e., the
     * path to the assets directory in the public path.
     *
     * @var string
     */
    protected $dir;
    
    /**
     * Full path to manifest file.
     *
     * @var string
     */
    protected $path;
    
    /**
     * Manifest file contents as array.
     *
     * @var array
     */
    protected $contents = [];
    
    protected $assets;
    
    /**
     * @param Assets $assets
     * @param string $dir
     * @throw Exception\RuntimeException
     */
    public function __construct($assets, $dir)
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0774, true);
        }
        
        $this->dir     = $dir;
        $this->assets  = $assets;
        $this->getManifestContents();
    }
    
    public function compile($manifestFiles, $useDigest, $gzCompression, $gzCompressionLevel = 9)
    // public function compile($manifestFiles)
    {
        $manifestFiles = (array)$manifestFiles;
        $files = $this->assets->finder()->findAll($manifestFiles);
        // $useDigest = $this->asset
        
        // exit;
        // $fileNames = (array)$fileNames;
        
        // foreach ($fileNames as $fileName) {
            // $file = $this->assets->finder()->find($fileName);
            
            // if (!$file) {
                // throw new Exception\RuntimeException(
                    // sprintf(
                        // "Couldn't find asset file %s",
                        // $fileName
                    // )
                // );
            // }
        foreach ($files as $file) {
            $subPathsPath = $file->subPathsPath();
            if ($subPathsPath) {
                $subPathsPath .= '/';
            }
            
            if ($this->assets->getCompiler($file->type())) {
                $compiled = $this->assets->compileFile($file);
                $realFilePath = $compiled['tempFilePath'];
            } else {
                $realFilePath = $file->originalFilePath();
            }
            
            $contents = file_get_contents($realFilePath);
            $logicalFileName = $subPathsPath . $file->name() . '.' . $file->type();
            
            if (!$useDigest) {
                $digest = md5($contents);
                $assetFileName = $subPathsPath . $file->name() . '-' . $digest . '.' . $file->type();
            } else {
                $digest = null;
                $assetFileName = $logicalFileName;
            }
            
            $this->removeCurrentFile($logicalFileName);
            
            $this->contents['files'][$assetFileName] = [
                'logicalPath' => $logicalFileName,
                'mtime'       => filemtime($realFilePath),
                'size'        => filesize($realFilePath),
                'digest'      => $digest
            ];
            
            $this->contents['assets'][$logicalFileName] = $assetFileName;
            
            $assetDir = $this->dir . '/' . $subPathsPath;
            
            if (!is_dir($assetDir)) {
                mkdir($assetDir, 0774, true);
            }
            
            copy($realFilePath, $this->dir . '/' . $assetFileName);
            
            if ($gzCompression) {
                $gzFile = $this->dir . '/' . $assetFileName . '.gz';
                $gzdata = gzencode($contents, $gzCompressionLevel);
                
                if (file_put_contents($gzFile, $gzdata)) {
                    # log?
                } else {
                    throw new Exception\RuntimeException(
                        sprintf("Couldn't write file %s", $gzFile)
                    );
                }
            }
        }
    }
    
    public function contents()
    {
        return $this->contents;
    }
    
    public function reset()
    {
        $this->contents = [
            'files'  => [],
            'assets' => []
        ];
        return $this;
    }
    
    public function save()
    {
        $json = json_encode($this->contents);
        file_put_contents($this->path, $json);
    }
    
    protected function getManifestContents()
    {
        if (!$this->path = $this->findFile()) {
            $this->path  = $this->createFile();
            $this->reset();
        } else {
            $contents = json_decode(
                file_get_contents($this->path),
                true
            );
            
            if ($contents) {
                $this->contents = $contents;
            }
        }
        $this->normalizePath($this->path);
    }
    
    protected function findFile()
    {
        $patt = $this->dir . '/manifest-*.json';
        
        if ($matches = glob($patt)) {
            return $matches[0];
        } else {
            return false;
        }
    }
    
    protected function createFile()
    {
        $name = 'manifest-' . md5(rand(1000, PHP_INT_MAX)) . '.json';
        return $this->dir . '/' . $name;
    }
    
    protected function normalizePath(&$path)
    {
        $path = str_replace('\\', '/', $path);
    }
    
    protected function removeCurrentFile($logicalFileName)
    {
        if (isset($this->contents['assets'][$logicalFileName])) {
            $assetFileName = $this->contents['assets'][$logicalFileName];
            $patt = $this->dir . '/' . $assetFileName . '*';
            $ms   = glob($patt);
            
            if ($ms) {
                foreach ($ms as $m) {
                    unlink($m);
                }
            }
            
            unset($this->contents['assets'][$logicalFileName]);
            unset($this->contents['files'][$assetFileName]);
        }
    }
}
