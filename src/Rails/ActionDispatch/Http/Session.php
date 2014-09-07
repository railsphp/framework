<?php
namespace Rails\ActionDispatch\Http;

use Rails\ArrayHelper\GlobalVariableIndex;
use Rails\ActionDispatch\Cookies\CookieJar;

class Session implements \IteratorAggregate
{
    /**
     * @var CookieJar
     */
    protected $cookieJar;
    
    public function getIterator()
    {
        return new \ArrayIterator($_SESSION);
    }
    
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }
    
    public function __get($key)
    {
        return $this->get($key);
    }
    
    public function set($key, $value)
    {
        if (is_object($value)) {
            $this->$key = $value;
            $_SESSION[$key] = $value;
        } elseif (is_array($value)) {
            $arr = new GlobalVariableIndex($value, '_SESSION', $key);
            $this->$key = $arr;
        } else {
            $_SESSION[$key] = $value;
        }
        return $this;
    }
    
    public function get($key)
    {
        if (isset($_SESSION[$key])) {
            if (is_array($_SESSION[$key])) {
                $this->$key = new GlobalVariableIndex($_SESSION[$key], '_SESSION', $key);
                return $this->$key;
            } elseif (is_object($_SESSION[$key])) {
                $this->$key = $_SESSION[$key];
                return $this->$key;
            } else {
                return $_SESSION[$key];
            }
        }
        return null;
    }
    
    public function setCookieJar(CookieJar $cookieJar)
    {
        $this->cookieJar = $cookieJar;
    }
    
    public function name()
    {
        return session_name();
    }
    
    public function id()
    {
        return session_id();
    }
    
    public function delete($key)
    {
        unset($this->$key, $_SESSION[$key]);
    }
    
    /**
     * Starts the PHP session, if not yet started.
     */
    public function start($name = null, $id = null)
    {
        if (!session_id()) {
            if ($name) {
                session_name($name);
            }
            if ($id) {
                session_id($this->id);
            }
            session_start();
            return true;
        }
        return false;
    }
    
    /**
     * Destroys the PHP session.
     */
    public function destroy()
    {
        $_SESSION = [];
        
        if ($this->cookieJar) {
            $this->cookieJar->remove($this->name());
        } else {
            $params = session_get_cookie_params();
            setcookie($this->name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
}
