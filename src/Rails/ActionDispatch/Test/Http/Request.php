<?php
namespace Rails\ActionDispatch\Test\Http;

use Rails\ActionDispatch\Http\Request as Base;
use Rails\ActionDispatch\Http\Cookies\CookieJar;

class Request extends Base
{
    protected $serverVars;
    
    public function setServerVars($serverVars)
    {
        $this->serverVars = $serverVars;
    }
    
    public function getParam($name)
    {
        $name = strtoupper($name);
        if (isset($this->serverVars[$name])) {
            return $this->serverVars[$name];
        }
        return null;
    }
    
    public function setFormat($format)
    {
        $this->format = $format;
    }
    
    /**
     * Parent calls `getallheaders` which is not available on CLI till PHP 5.5.7.
     */
    public function format()
    {
        if ($this->format === null) {
            if (null !== ($format = $this->parameters->getParam('format'))) {
                $this->format = $format;
            } else {
                $this->format = 'html';
            }
        }
        return $this->format;
    }
    
    public function cookieJar()
    {
        if (!$this->cookieJar) {
            $this->cookieJar = new CookieJar([]);
        }
        return $this->cookieJar;
    }
    
    public function resetCookieJar()
    {
        $this->cookieJar = new CookieJar([]);
        return $this;
    }
}
