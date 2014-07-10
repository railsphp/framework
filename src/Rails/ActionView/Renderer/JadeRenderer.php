<?php
namespace Rails\ActionView\Renderer;

use Everzet\Jade\Lexer\Lexer;
use Everzet\Jade\Parser;
use Everzet\Jade\Dumper\PHPDumper;
use Rails;
use Rails\ActionView\Jade;

class JadeRenderer extends Base
{
    protected $cachePath;
    
    protected $jade;
    
    public function __construct()
    {
        $this->setCachePath(
            $this->getService('rails.config')['paths']['root']->expand('tmp', 'cache', 'jade')
        );
        
        $this->initParser();
    }
    
    public function setCachePath($cachePath)
    {
        $this->cachePath = $cachePath; 
    }
    
    public function processRender()
    {
        if ($this->cachePath) {
            return $this->renderWithCache();
        } else {
            return $this->renderWithoutCache();
        }
    }
    
    protected function renderWithCache()
    {
        $this->jade->computeCacheKey(
            $this->template->filePath(),
            $this->getService('rails.config')['paths']['root']->toString()
        );
        
        $cachedFile = $this->jade->cache($this->template->filePath());
        ob_start();
        require $cachedFile;
        return ob_get_clean();
    }
    
    protected function renderWithoutCache()
    {
        $contents = $this->jade->render($this->template->filePath());
        $fh       = tmpfile();
        $metaData = stream_get_meta_data($fh);
        $fileName = $metaData["uri"];
        
        fwrite($fh, $contents);
        
        ob_start();
        require $fileName;
        fclose($fh);
        return ob_get_clean();
    }
    
    protected function initParser()
    {
        if ($this->cachePath) {
            if (!is_dir($this->cachePath)) {
                mkdir($this->cachePath, 0777, true);
            }
        }
        
        $this->jade = new Jade(
            new Parser(new Lexer()),
            new PHPDumper(),
            $this->cachePath
        );
    }
}
