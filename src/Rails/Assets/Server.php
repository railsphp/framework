<?php
namespace Rails\Assets;

use Rails\ActiveSupport\MimeTypes;

class Server
{
    public static function dispatchRequest($app)
    {
        return (new self())->serve($app);
    }
    
    public function serve($app)
    {
        $fileType = $app->request()->format();
        $filePath = null;
        $filename = (string)$app->parameters()->path . ($fileType ? '.' . $fileType : '');
        
        if ($fileType == 'css' || $fileType == 'js') {
            $data = $app->getService('assets')->compileFile(
                $filename,
                !(bool)$app->parameters()->body
            );
            
            if ($data) {
                $filePath = $data['tempFilePath'];
            }
        } else {
            $file = $app->getService('assets')->findFile($filename);
            
            if ($file) {
                $filePath = $file->originalFilePath();
            }
        }
        
        $response = $app->response();
        
        if ($filePath) {
            $mtime = filemtime($filePath);
            $etag  = md5($filePath . $mtime);
            
            if ($this->notModified($filePath, $etag)) {
                $this->setNotModifiedHeaders($response);
                return;
            }
            
            $response
                ->addHeader('Last-Modified', date('D, d M Y H:i:s T'))
                ->addHeader('ETag', $etag)
                ->addHeader('Cache-control', 'public, must-revalidate')
                ->setBody(file_get_contents($filePath))
                ->setContentType(MimeTypes::getMimeType($fileExt));
        } else {
            $response->setStatus(404);
        }
    }
    
    protected function notModified($filePath, $etag)
    {
        $mtime   = filemtime($filePath);
        $headers = getallheaders();
        
        if (isset($headers['If-Modified-Since'])) {
            $modSince = date($headers['If-Modified-Since']);
            if ($mtime == $modSince) {
                return true;
            }
        }
        
        if (isset($headers['If-None-Match'])) {
            if ($headers['If-None-Match'] == $etag) {
                return true;
            }
        }
        
        return false;
    }
    
    protected function setNotModifiedHeaders($response)
    {
        $response->addHeader('Cache-control', 'no-cache');
        $response->setStatus(304);
    }
}
