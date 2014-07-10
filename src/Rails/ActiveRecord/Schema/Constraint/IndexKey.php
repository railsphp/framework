<?php
namespace Rails\ActiveRecord\Schema\Constraint;

class IndexKey extends AbstractConstraint
{
    /**
     * @var string
     */
    protected $specification = 'INDEX %s(%s)';
    
    protected $name = '';

    public function __construct($columns = null, $name = null)
    {
        parent::__construct($columns);
        $this->name = $name;
    }
    
    /**
     * @return array
     */
    public function getExpressionData()
    {
        $colCount     = count($this->columns);
        $newSpecParts = array_fill(0, $colCount, '%s');
        $newSpecTypes = array_fill(0, $colCount, self::TYPE_IDENTIFIER);
        
        $parameters = $this->columns;
        
        if ($this->name) {
            array_unshift($parameters, $this->name);
            $newSpecTypes[] = self::TYPE_IDENTIFIER;
        }

        $newSpec = sprintf(
            $this->specification,
            $this->name ? '%s' : '',
            implode(', ', $newSpecParts)
        );

        return array(array(
            $newSpec,
            $parameters,
            $newSpecTypes,
        ));
    }
}
