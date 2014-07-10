<?php
namespace Rails\Assets\Tasks;

use Rails\Console\Task\Task;
use Rails\Assets\Manifest;

class CompileTask extends Task
{
    protected function configure()
    {
        $this
            ->setName('assets:compile')
            ->setDescription('Compile all or a specific manifest file.')
            ->addArgument(
                'file_name',
                'optional',
                'Name of file to compile.'
            );
        ;
    }
    
    protected function task()
    {
        $fileName = $this->arg('file_name');
        
        if ($fileName) {
            $files = $fileName;
        } else {
            $files = $this->app->config()['assets']['precompile'];
        }
        
        $this->compileManifestFiles($files);
    }
    
    protected function compileManifestFiles($files)
    {
        $assets = $this->app->getService('assets');
        
        # Apply production config.
        $config     = clone $this->app->config();
        $configFile = $config['paths']['root']->extend('config', 'environments', 'production.php');
        $productionConfig = require $configFile;
        $productionConfig($config);
        
        // $config = clone $this->app->config()['assets'];
        $dir    = $this->app->config()['paths']['public_path']->expand(
                    substr($assets->prefix(), 1)
                  );
        
        $manifest = new Manifest(
            $assets,
            $dir
        );
        
        $manifest->compile($files, $config['digest'], $config['gz_compression'], $config['gz_compression_level']);
        $manifest->save();
    }
}
