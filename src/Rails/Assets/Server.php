<?php
namespace Rails\Assets;

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
            $mtime   = filemtime($filePath);
            $etag  = md5($filePath . $mtime);
            
            if ($this->notModified($filePath, $etag)) {
                $this->setNotModifiedHeaders($response);
                return;
            }
            
            $response->addHeader('Last-Modified', date('D, d M Y H:i:s T'));
            $response->addHeader('ETag', $etag);
            $response->addHeader('Cache-control', 'public, must-revalidate');
            
            $response->setBody(file_get_contents($filePath));
            $this->setContentType($fileType, $response);
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
    
    protected function setContentType($fileExt, $response)
    {
        switch ($fileExt) {
            case 'js':
                $contentType = 'text/javascript';
                break;
            
            case 'css':
                $contentType = 'text/css';
                break;
            
            case 'jpeg':
                $contentType = 'image/jpeg';
                break;
                
            case 'jpg':
                $contentType = 'image/jpeg';
                break;
                
            case 'png':
                $contentType = 'image/png';
                break;
                
            case 'gif':
                $contentType = 'image/gif';
                break;
                
            case 'ico':
                $contentType = 'image/x-icon';
                break;
                
            case 'svg':
                $contentType = 'image/svg+xml';
                break;
                
            case 'ttf':
                $contentType = 'application/x-font-ttf';
                break;
                
            case 'woff':
                $contentType = 'application/font-woff';
                break;
            
            default:
                $contentType = 'application/octet-stream';
                return;
        }
        
        $response->setContentType($contentType);
    }
}
