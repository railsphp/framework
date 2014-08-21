<?php
namespace Rails\ActionDispatch\Http;

use Rails\ActionDispatch\Exception;

class Response
{
    protected $charset   = 'utf8';
    
    protected $committed = false;
    
    protected $headers   = [];
    
    protected $status;
    
    protected $body;
    
    protected $cookieJar;
    
    public function setCharset($charset)
    {
        $this->charset = $charset;
        return $this;
    }
    
    /**
     * If charset isn't passed, $charset is set.
     *
     * ´´´
     * setContentType('application/javascript');
     * setContentType('application/javascript; charset=utf8');
     * ´´´
     *
     * @param string $contentType
     */
    public function setContentType($contentType)
    {
        if (is_bool(strpos($contentType, 'charset=')) && $this->charset) {
            $contentType .= '; charset=' . $this->charset;
        }
        
        $this->headers['Content-Type'] = $contentType;
        return $this;
    }
    
    public function setStatus($status)
    {
        if (!is_int($status)) {
            $status = StatusCodes::getCode($status);
        }
        $this->status = $status;
        return $this;
    }
    
    public function setLocation($location)
    {
        $this->headers['Location'] = $location;
        return $this;
    }
    
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }
    
    public function setCookieJar(Cookies\CookieJar $cookieJar)
    {
        $this->cookieJar = $cookieJar;
    }
    
    public function addHeader($name, $value)
    {
        if (!is_string($name)) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    "First argument must be string, %s passed",
                    gettype($name)
                )
            );
        }
        if (!is_string($value) && !is_int($value)) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    "Second argument must be either string or int, %s passed",
                    gettype($value)
                )
            );
        }
        
        $this->headers[$name] = $value;
        return $this;
    }
    
    public function removeHeader($name)
    {
        unset($this->headers[$name]);
        return $this;
    }
    
    public function charset()
    {
        return $this->charset;
    }
    
    public function contentType()
    {
        if (!empty($this->headers['Content-Type'])) {
            return $this->headers['Content-Type'];
        }
        return null;
    }
    
    public function status()
    {
        return $this->status;
    }
    
    public function location()
    {
        return $this->location;
    }
    
    public function body()
    {
        return $this->body;
    }
    
    public function headers()
    {
        return $this->headers;
    }
    
    public function commit()
    {
        if ($this->committed) {
            return;
        }
        
        $this->clearBuffers();
        $this->setCookies();
        $this->sendHeaders();
        $this->sendStatus();
        
        echo $this->body;
        
        $this->body      = null;
        $this->committed = true;
    }
    
    public function isCommitted()
    {
        return $this->committed;
    }
    
    protected function setCookies()
    {
        if ($this->cookieJar) {
            $this->cookieJar->write($this);
        }
    }
    
    protected function clearBuffers()
    {
        if (($status = ob_get_status()) && $status['level']) {
            foreach (range(0, $status['level']) as $i) {
                ob_end_clean();
            }
        }
    }
    
    protected function sendHeaders()
    {
        foreach ($this->headers as $header => $value) {
            if (is_int($header)) {
                header($value);
            } else {
                header($header . ': ' . $value);
            }
        }
    }
    
    protected function sendStatus()
    {
        if ($this->status) {
            http_response_code($this->status);
        }
    }
}
