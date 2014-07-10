<?php
namespace Rails\ActiveRecord\Schema\Column;

use Zend\Db\Sql\Ddl\Column\Column as ZfColumn;

class Text extends ZfColumn
{
    /**
     * Tiny, Medium, Long, or empty.
     * @var string
     */
    protected $size;

    /**
     * @var string
     */
    protected $specification = '%s %sTEXT %s';

    /**
     * @param string $name
     * @param string $size (for MySQL)
     */
    public function __construct($name, $size = '')
    {
        $this->name = $name;
        $this->size = $size;
    }

    /**
     * @return array
     */
    public function getExpressionData()
    {
        $spec   = $this->specification;
        $params = array();

        $types    = array(self::TYPE_IDENTIFIER, self::TYPE_LITERAL);
        $params[] = $this->name;
        
        $params[] = strtoupper($this->size);

        $types[]  = self::TYPE_LITERAL;
        $params[] = (!$this->isNullable) ? 'NOT NULL' : '';

        $types[]  = ($this->default !== null) ? self::TYPE_VALUE : self::TYPE_LITERAL;

        return array(array(
            $spec,
            $params,
            $types,
        ));
    }
}
