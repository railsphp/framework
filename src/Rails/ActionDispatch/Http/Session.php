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
    
    public function __set($prop, $value)
    {
        $this->set($prop, $value);
    }
    
    public function __get($prop)
    {
        return $this->get($prop);
    }
    
    public function set($key, $value)
    {
        if (is_object($value)) {
            $this->$prop = $value;
            $_SESSION[$prop] = $value;
        } elseif (is_array($value)) {
            $arr = new GlobalVariableIndex($value, '_SESSION', $prop);
            $this->$prop = $arr;
        } else {
            $_SESSION[$prop] = $value;
        }
        return $this;
    }
    
    public function get($prop)
    {
        if (isset($_SESSION[$prop])) {
            if (is_array($_SESSION[$prop])) {
                $this->$prop = new GlobalVariableIndex($_SESSION[$prop], '_SESSION', $prop);
                return $this->$prop;
            } elseif (is_object($_SESSION[$prop])) {
                $this->$prop = $_SESSION[$prop];
                return $this->$prop;
            } else {
                return $_SESSION[$prop];
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
    
    public function delete($prop)
    {
        unset($this->$prop, $_SESSION[$prop]);
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
