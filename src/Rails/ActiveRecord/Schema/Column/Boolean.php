<?php
namespace Rails\ActiveRecord\Schema\Column;

use Zend\Db\Sql\Ddl\Column\Boolean as ZfBoolean;

class Boolean extends ZfBoolean
{
    /**
     * Adds default option.
     */
    public function getExpressionData()
    {
        $data = parent::getExpressionData();
        
        if ($this->default !== null) {
            $data[0][0]  .= ' DEFAULT %s';
            $data[0][1][] = $this->default;
            $data[0][2][] = self::TYPE_VALUE;
        }
        
        return $data;
    }
}
