<?php
namespace Rails\ActiveRecord\Schema\Column;

use Zend\Db\Sql\Ddl\Column\Column as ZfColumn;

class PrimaryKey extends ZfColumn
{
    protected $length = 11;

    protected $driverName;
    
    protected $unsigned = false;

    /**
     * @param null|string $name
     * @param int $length
     */
    public function __construct($name, $driverName)
    {
        $this->name    = $name;
        $this->driverName = $driverName;
    }
    
    public function setLength($length)
    {
        $this->length = $length;
    }
    
    public function setUnsigned($value)
    {
        $this->unsigned = (bool)$value;
    }

    /**
     * @return array
     */
    public function getExpressionData()
    {
        $params = [];
        
        if ($this->driverName == 'Mysql') {
            $types = [self::TYPE_IDENTIFIER, self::TYPE_LITERAL, self::TYPE_LITERAL];
            $spec  = '%s INT(%s) NOT NULL AUTO_INCREMENT %s';
            
            $params[] = $this->name;
            $params[] = $this->length;
            
            if ($this->unsigned) {
                $params[] = 'UNSIGNED';
            } else {
                $params[] = '';
            }
        } elseif ($this->driverName == 'Sqlite') {
            $types = [self::TYPE_IDENTIFIER];
            $spec  = '%s INTEGER PRIMARY KEY AUTOINCREMENT';
            
            $params[] = $this->name;
        }
        
        return array(array(
            $spec,
            $params,
            $types,
        ));
    }
}
