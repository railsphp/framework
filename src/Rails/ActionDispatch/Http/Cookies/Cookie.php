<?php
namespace Rails\ActionDispatch\Http\Cookies;

use DateTime;
use Rails\ActionDispatch\Exception;

class Cookie
{
    protected $name;
    
    protected $value;
    
    protected $expire;
    
    protected $path;
    
    protected $domain;
    
    protected $secure   = false;
    
    protected $httponly = false;
    
    protected $raw      = false;
    
    /**
     * $expire may be int or string. If string, it will be passed to strtotime().
     */
    public function __construct($name, $value, $expire = null, $path = null, $domain = null, $secure = false, $httponly = false, $raw = false)
    {
        $this->name     = (string)$name;
        $this->domain   = (string)$domain;
        $this->value    = (string)$value;
        $this->expire   = $expire;
        $this->path     = $path;
        $this->secure   = $secure;
        $this->httponly = $httponly;
        $this->raw      = $raw;
        
        if (!$this->name) {
            throw new Exception\InvalidArgumentException('Cookies must have a name');
        }
        if (preg_match("/[=,; \t\r\n\013\014]/", $this->name)) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    "Cookie name (%s) cannot contain these characters: =,; \\t\\r\\n\\013\\014",
                    $this->name
                )
            );
        }
        if ($this->raw && preg_match("/[=,; \t\r\n\013\014]/", $this->value)) {
            throw new Exception\InvalidArgumentException(
                sprintf("Raw cookie value cannot contain these characters: =,; \\t\\r\\n\\013\\014 (%s)", $this->value)
            );
        }
        if (!is_scalar($this->value)) {
            throw new Exception\InvalidArgumentException(
                sprintf("Cookie value must be a scalar value, %s passed", gettype($this->value))
            );
        }
        if (is_string($this->expire)) {
            $time = strtotime($this->expire);
            if (!$time) {
                throw new Exception\InvalidArgumentException(
                    sprintf("Invalid expiration time: %s", $this->expire)
                );
            }
            $this->expire = $time;
        }
        if ($this->expire !== null && !is_int($this->expire)) {
            throw new Exception\InvalidArgumentException(
                sprintf("Cookie expiration time must be an integer, %s passed", gettype($this->expire))
            );
        }
    }
    
    public function value()
    {
        return $this->value;
    }
    
    public function set()
    {
        if ($this->raw) {
            setrawcookie($this->name, $this->value, $this->expire, $this->path, $this->domain, $this->secure, $this->httponly);
        } else {
            setcookie($this->name, $this->value, $this->expire, $this->path, $this->domain, $this->secure, $this->httponly);
        }
    }
    
    public function toString()
    {
        $pairs   = [];
        $pairs[] = $this->name . '=' . ($this->raw ? $this->value : urlencode($this->value));
        
        if ($this->expire) {
            $pairs[] = 'Expires=' . date(DateTime::COOKIE, $this->expire);
        }
        if ($this->domain) {
            $pairs[] = 'Domain=' . $this->domain;
        }
        if ($this->path) {
            $pairs[] = 'Path=' . $this->path;
        }
        if ($this->secure) {
            $pairs[] = 'Secure';
        }
        if ($this->httponly) {
            $pairs[] = 'HttpOnly';
        }
        return implode('; ', $pairs);
    }
}
