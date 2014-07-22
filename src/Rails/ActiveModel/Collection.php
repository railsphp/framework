<?php
namespace Rails\ActiveModel;

use Closure;
use Rails\Exception\DeprecatedMethodException;

class Collection implements \ArrayAccess, \Iterator, \Countable
{
    /* ArrayAccess { */
    protected $members  = [];
    
    protected $position = 0;
    
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->members[] = $value;
        } else {
            $this->members[$offset] = $value;
        }
    }
    
    public function offsetExists($offset)
    {
        return isset($this->members[$offset]);
    }
    
    public function offsetUnset($offset)
    {
        unset($this->members[$offset]);
    }
    
    public function offsetGet($offset)
    {
        return isset($this->members[$offset]) ? $this->members[$offset] : null;
    }
    /* } Iterator {*/
    
    public function rewind()
    {
        reset($this->members);
        $this->position = key($this->members);
    }

    public function current()
    {
        return $this->members[$this->position];
    }

    public function key()
    {
        return key($this->members);
    }

    public function next()
    {
        next($this->members);
        $this->position = key($this->members);
    }

    public function valid()
    {
        return array_key_exists($this->position, $this->members);
    }
    /* } Countable { */
    
    public function count()
    {
        return count($this->members);
    }
    /* } */
    
    public function __construct(array $members = array())
    {
        $this->members = $members;
    }
    
    public function merge()
    {
        foreach (func_get_args() as $coll) {
            if ($coll instanceof self) {
                $coll = $coll->members();
            }
            $this->members = array_merge($this->members, $coll);
        }
        return $this;
    }
    
    public function toArray()
    {
        return $this->members;
    }
    
    /**
     * Each (experimental)
     *
     * If string is passed, it'll be taken as method name to be called.
     * Eg. $posts->each('destroy'); - All posts will be destroyed.
     * In this case, $params for the method may be passed.
     *
     * A Closure may also be passed.
     */
    public function each($function, array $params = array())
    {
        if (is_string($function)) {
            foreach ($this->members() as $m) {
                call_user_func_array(array($m, $function), $params);
            }
        } elseif ($function instanceof Closure) {
            foreach ($this->members() as $idx => $m) {
                $function($m, $idx);
            }
        } else {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    'Argument 1 must be an either a string or a Closure, %s passed.',
                    gettype($model)
                )
            );
        }
    }
    
    public function reduce($var, Closure $block)
    {
        foreach ($this->members() as $m) {
            $var = $block($var, $m);
        }
        return $var;
    }
    
    public function sort(Closure $criteria)
    {
        usort($this->members, $criteria);
        $this->rewind();
        return $this;
    }
    
    public function unshift($values)
    {
        if ($model instanceof Base) {
            $model = array($model);
        } elseif (!$model instanceof self) {
            throw new Exception\InvalidArgumentException(
                sprintf('Argument must be an instance of either ActiveRecord\Base or ActiveRecord\Collection, %s passed.', gettype($model))
            );
        }
        
        foreach ($model as $m) {
            array_unshift($this->members, $m);
        }
        
        return $this;
    }
    
    /**
     * Searches objects for a property with a value and returns object.
     */
    public function search($prop, $value)
    {
        foreach ($this->members as $obj) {
            if ($obj->getProperty($prop) == $value)
                return $obj;
        }
        return false;
    }
    
    # Returns a Collection with the models that matches the options.
    # Eg: $posts->select(array('is_active' => true, 'user_id' => 4));
    # If Closure passed as $opts, the model that returns == true on the function
    # will be added.
    public function select($criteria)
    {
        $selected = [];
        
        if (is_array($criteria)) {
            foreach ($this as $member) {
                foreach ($criteria as $propName => $value) {
                    if ($member->getProperty($propName) != $value) {
                        continue;
                    }
                    $selected[] = $member;
                }
            }
        } elseif ($criteria instanceof Closure) {
            foreach ($this as $member) {
                if ($criteria($member)) {
                    $selected[] = $member;
                };
            }
        }
        
        return new static($selected);
    }
    
    /**
     * Get the model with the max value according to $criteria.
     * If $criteria is a string, it's assumed it's the name of either an
     * attribute or a property of the model, and the comparison is a simple
     * `return $a > $b ? $a : $b;`. If this isn't enough, a Closure may be
     * passed as $criteria. It will receive 2 models as arguments and one
     * of them must be returned.
     *
     * @param string|Closure $criteria
     * @return Base
     */
    public function max($criteria)
    {
        if (!$this->members) {
            return false;
        } elseif (count($this->members) < 2) {
            return current($this->members);
        }
        
        if (is_string($criteria)) {
            $propName = $criteria;
            $criteria = function ($a, $b) use ($propName) {
                if ($a->getProperty($propName) > $b->getProperty($propName)) {
                    return $a;
                } else {
                    return $b;
                }
            };
        } elseif (!$criteria instanceof Closure) {
            throw new Exception\InvalidArgumentException(sprintf(
                "Argument 1 must be either string or Closure, %s passed",
                gettype($criteria)
            ));
        }
        
        return array_reduce($this->members, $criteria, reset($this->members));
    }
    
    public function none()
    {
        return empty($this->members);
    }
    
    public function any()
    {
        return (bool)$this->members;
    }
    
    /**
     * TODO: xml shouldn't be created here.
     */
    public function toXml(array $options)
    {
        return (new Serializer\Xml())->serializeCollection($this);
    }
    
    public function asJson()
    {
        $json = [];
        foreach ($this->members as $member) {
            $json[] = $member->asJson();
        }
        return $json;
    }
    
    public function toJson()
    {
        return json_encode($this->asJson());
    }
    
    /**
     * Returns an array of the attributes in the models.
     * $attrs could be a string of a single attribute we want, and
     * an indexed array will be returned.
     * If $attrs is an array of many attributes, an associative array will be returned.
     *
     * $collection->attributes(['id', 'createdAt']);
     * No -> $collection->attributes('id', 'name');
     */
    public function getAttributes($attrs)
    {
        $modelsAttrs = array();
        
        if (is_string($attrs)) {
            foreach ($this as $m) {
                $modelsAttrs[] = $m->getProperty($attrs);
            }
        } else {
            foreach ($this->members() as $m) {
                $modelAttrs = [];
                foreach ($attrs as $attr) {
                    $modelAttrs[$attr] = $m->getProperty($attr);
                }   
                $modelsAttrs[] = $modelAttrs;
            }
        }
        
        return $modelsAttrs;
    }
    
    # Removes dupe models based on id or other attribute.
    public function unique($attr = 'id')
    {
        $checked = array();
        foreach ($this->members() as $k => $obj) {
            if (in_array($obj->getProperty($attr), $checked)) {
                unset($this->members[$k]);
            } else {
                $checked[] = $obj->getProperty($attr);
            }
        }
        return $this;
    }
    
    # array_slices the collection.
    public function slice($offset, $length = null)
    {
        $clone = clone $this;
        $clone->members = array_slice($clone->members, $offset, $length);
        return $clone;
    }
    
    /**
     * If criteria returns a non-empty value, that member is removed.
     */
    public function remove(Closure $criteria)
    {
        foreach ($this->members as $k => $member) {
            if ($criteria($member)) {
                unset($this->members[$k]);
            }
        }
        $this->members = array_values($this->members);
        return $this;
    }
    
    public function replace($replacement)
    {
        if ($replacement instanceof self) {
            $this->members = $replacement->members();
        } elseif (is_array($replacement)) {
            $this->members = $replacement;
        } else {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    "Argument must be instance of Collection or an array, %s passed",
                    gettype($replacement)
                )
            );
        }
    }
}
