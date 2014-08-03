<?php
namespace Rails\Assets\Processor\Directives;

use DirectoryIterator;
use SplFileInfo;
use Rails\Assets\Exception;
use Rails\Assets\Processor\Processor;
use Rails\Assets\File\Copy as FileCopy;

class Executor
{
    protected $file;
    
    protected $lines;
    
    protected $mode;
    
    protected $lineNumber;
    
    protected $requiredSelf = false;
    
    public function __construct($file, $processor)
    {
        $this->file      = $file;
        $this->processor = $processor;
    }
    
    public function execute(array $directives, $mode = null)
    {
        $this->mode = $mode;
        $this->executeDirectives($directives);
        $this->requireSelf();
    }
    
    public function requireFile($filePath)
    {
        if ($this->isRelative($filePath)) {
            $file = $this->requireRelativeFile($filePath);
        } elseif ($this->isAbsolute($filePath)) {
            # TODO
        } else {
            $file = $this->findFile($filePath);
        }
        
        if (!$file) {
            throw new Exception\FileNotFoundException(
                sprintf(
                    "Couldn't find required file '%s' (%s:%s)",
                    $filePath,
                    $this->file->originalFilePath(),
                    $this->lineNumber
                )
            );
        }
        
        $this->processor->listFiles($file);
    }
    
    public function requireDir($requiredDir)
    {
        $dirPath = realpath($this->file->fullDir() . '/' . $requiredDir);
        
        if (!is_dir($dirPath)) {
            throw new Exception\RuntimeException(
                sprintf(
                    "Required dir '%s' wasn't found. Searched for '%s'\nin: '%s'",
                    $requiredDir,
                    $dirPath,
                    $this->file->originalFilePath()
                )
            );
        }
        
        $this->requireDirectory($dirPath);
    }
    
    public function requireTree($basePath)
    {
        $dirPath = realpath($this->file->fullDir() . '/' . $basePath);
        
        if (!is_dir($dirPath)) {
            throw new Exception\RuntimeException(
                sprintf(
                    "Required tree '%s' wasn't found. Searched for '%s'\nin: '%s'",
                    $basePath,
                    $dirPath,
                    $this->file->originalFilePath()
                )
            );
        }
        
        $dir = new DirectoryIterator($dirPath);
        
        $this->requireDirectory($dirPath);
        
        foreach ($dir as $fileInfo) {
            if (!$fileInfo->isDot() && $fileInfo->isDir()) {
                $this->requireDirectory($fileInfo->getRealPath());
            }
        }
    }
    
    protected function requireSelf()
    {
        if (!$this->requiredSelf) {
            $this->processor->addFile($this->file);
            $this->requiredSelf = true;
        }
    }
    
    protected function requireRelativeFile($filePath)
    {
        // $file = $this->file->finder()->find(
    }
    
    protected function isRelative($path)
    {
        return strpos($path, '.') === 0;
    }
    
    protected function isAbsolute($path)
    {
        return strpos($path, '/') === 0 || strpos($path, ':') === 1;
    }
    
    protected function findFile($filePath)
    {
        return $this->file->finder()->findWithPrependedPaths(
            $filePath . '.' . $this->file->type(),
            $this->file->rootPath()
        );
    }
    
    protected function executeDirectives($directives)
    {
        foreach ($directives as $directive) {
            extract($directive);
            $this->lineNumber = $line;
            
            switch ($command) {
                case 'require_directory':
                    $this->requireDir($argument);
                    break;
                
                case 'require_tree':
                    $this->requireTree($argument);
                    break;
                
                case 'require_self':
                    $this->requireSelf();
                    break;
                
                case 'require':
                    $this->requireFile($argument);
                    break;
                
                case 'include':
                    $this->includeFile($argument);
                    break;
                
                default:
                    throw new Exception\RuntimeException(
                        sprintf(
                            "Invalid directive '%s'\nin: %s:%s",
                            $command,
                            $this->file->originalFilePath(),
                            $line
                        )
                    );
            }
        }
    }
    
    protected function requireDirectory($dirPath)
    {
        $dir = new DirectoryIterator($dirPath);
        
        foreach ($dir as $fileInfo) {
            if (
                !$fileInfo->isDot() &&
                $fileInfo->isFile() &&
                str_replace('\\', '/', $fileInfo->getRealPath()) != $this->file->originalFilePath()
            ) {
                if ($neighbor = FileCopy::getNeighbor($this->file, $fileInfo->getRealPath())) {
                    $this->processor->listFiles($neighbor);
                }
            }
        }
    }
}
