<?php
namespace Rails\ActionDispatch\Http\Cookies;

use Rails\ActionDispatch\Http\Response;

/**
 * Holds Cookie objects, which will be set using common default
 * parameters.
 *
 * A cookie can be retrieved like a property, like $jar->cookieName;
 * or using the get('cookieName') method.
 * If the cookie doesn't exist, null is returned.
 *
 * Likewise, a cookie can be set like setting a property, like
 * $jar->cookieName = 'value';. If more parameters are to be set, an
 * array can be passed, where the value of the cookie is held in the "value" key,
 * like $jar->cookieName = ['value' => $cookieValue, 'expires' => '+1 year'];
 * The "value" key can actually be ommited, wich will result in an empty value for
 * the cookie.
 * A cookie can also be set by explicity calling the add() method.
 *
 * Request to delete a cookie in the client's browser by calling remove($cookieName).
 *
 * The cookies are actually sent to the client by calling write().
 */
class CookieJar
{
    /**
     * Holds cookies that will be added at the
     * end of the controller.
     */
    protected $jar = [];
        
    /**
     * To know if cookies were set or not.
     */
    protected $cookiesSet = false;
    
    protected $defaults   = [
        'expires'  => 0,
        'path'     => null,
        'domain'   => null,
        'secure'   => false,
        'httponly' => false,
        'raw'      => false
    ];
    
    public function __construct(array $defaults = [])
    {
        if ($defaults) {
            $this->setDefaults($defaults);
        }
    }
    
    public function __get($name)
    {
        return $this->get($name);
    }
    
    public function __set($prop, $params)
    {
        $this->add($prop, $params);
    }
    
    public function setDefaults(array $defaults)
    {
        $this->defaults = array_merge($this->defaults, $defaults);
    }
    
    public function jar()
    {
        return $this->jar;
    }
    
    public function get($name)
    {
        if (isset($this->jar[$name])) {
            return $this->jar[$name]->value();
        } elseif (isset($_COOKIE[$name])) {
            return $_COOKIE[$name];
        } else {
            return null;
        }
    }
    
    /**
     * The value of the cookie can be passed as `$params['value']`.
     * If `$value` is an array it's taken as `$params`.
     * If no value is passed, an empty string is set as value.
     *
     * @param string $name
     * @param string|array $value
     * @param array $params
     */
    public function add($name, $value = null, array $params = [])
    {
        if (is_array($value)) {
            $params = $value;
            if (isset($params['value'])) {
                $value = $params['value'];
                unset($params['value']);
            } else {
                $value = '';
            }
        }
    
        $params = array_merge($this->defaults, $params);
        $this->jar[$name] = new Cookie($name, $value, $params['expires'], $params['path'], $params['domain'], $params['secure'], $params['httponly'], $params['raw']);
        return $this;
    }
    
    public function remove($name, array $params = [])
    {
        $this->add($name, '', array_merge($params, [
            'expires' => 1
        ]));
        return $this;
    }
    
    public function write(Response $response = null)
    {
        foreach ($this->jar as $cookie) {
            if ($response) {
                $response->addHeader('Set-Cookie', $cookie->toString());
            } else {
                $cookie->set();
            }
        }
    }
}
