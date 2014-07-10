<?php
namespace Rails\ActionController\Base;

class ActionFilter implements \Serializable
{
    const ONLY   = 0;
    
    const EXCEPT = 1;
    
    protected $type;
    
    protected $actions;
    
    protected $actionName;
    
    public function __construct($type, $actions, $actionName)
    {
        $this->type       = $type;
        $this->actions    = $actions;
        $this->actionName = $actionName;
    }
    
    public function __invoke()
    {
        return $this->filter();
    }
    
    public function serialize()
    {
        return serialize([
            'type'       => $this->type,
            'actions'    => $this->actions,
            'actionName' => $this->actionName
        ]);
    }
    
    public function unserialize($data)
    {
        list (
            $this->type,
            $this->actions,
            $this->actionName
        ) = unserialize($data);
    }
    
    public function filter()
    {
        switch ($this->type) {
            case self::ONLY:
                return in_array($this->actions, $this->actionName);
            
            case self::EXCEPT:
                return !in_array($this->actions, $this->actionName);
        }

        throw new Exception\RuntimeException(
            sprintf('Unknown filter type %s', $this->type)
        );
    }
}
