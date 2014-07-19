<?php
namespace Rails\ActiveRecord\Metadata\Object;

use Zend\Db\Metadata\Object\ColumnObject as Zf2ColumnObject;
use Rails\ActiveSupport\Carbon\Carbon;

class ColumnObject extends Zf2ColumnObject
{
    protected static $TRUE_VALUES = [
        true,
        1,
        '1',
        't',
        'T',
        'true',
        'TRUE',
        'on',
        'ON'
        
    ];
    protected static $FALSE_VALUES = [
        false,
        0,
        '0',
        'f',
        'F',
        'false',
        'FALSE',
        'off',
        'OFF'
    ];
    
    protected $type;
    
    public static function createFromBase(Zf2ColumnObject $base)
    {
        $column = new self($base->name, $base->tableName, $base->schemaName);
        
        $column->ordinalPosition        = $base->ordinalPosition;
        $column->columnDefault          = $base->columnDefault;
        $column->isNullable             = $base->isNullable;
        $column->characterMaximumLength = $base->characterMaximumLength;
        $column->characterOctetLength   = $base->characterOctetLength;
        $column->numericPrecision       = $base->numericPrecision;
        $column->numericScale           = $base->numericScale;
        $column->numericUnsigned        = $base->numericUnsigned;
        $column->errata                 = $base->errata;
        
        $column->setType($column->simplifiedDataType($base->dataType));
        
        return $column;
    }
    
    /**
     * @param string $dataType  E.g. "tinyint(4)", "varchar(255)"
     */
    public function setDataType($dataType)
    {
        parent::setDataType($dataType);
        $this->extractMetadata($dataType);
        $this->setType($this->simplifiedDataType($dataType));
        return $this;
    }
    
    /**
     * This is expected to be used to manually set a more accurate
     * type for the column. For example, in MySQL boolean columns are
     * actually TinyInts.
     *
     * @TODO filter $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }
    
    public function type()
    {
        return $this->type;
    }
    
    public function isNumber()
    {
        return $this->type == 'integer' || $this->type == 'float';
    }
    
    public function typeCastForWrite($value)
    {
        if (!$this->isNumber()) {
            return $value;
        } elseif ($value === false) {
            return 0;
        } elseif ($value === true) {
            return 1;
        } elseif (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
            return $value;
        } else {
            return null;
        }
    }
    
    /**
     * Type cast
     * Casts $value to an appropiate type or instance.
     *
     * @param string $value
     */
    public function typeCast($value)
    {
        if ($value === null) {
            return $value;
        }
        
        switch ($this->type) {
            case 'string':
            case 'text':
            case 'binary':
            default:
                return $value;
            case 'integer':
                return $this->valueToInteger($value);
            case 'float':
                return (float)$value;
            case 'datetime':
            case 'timestamp':
                return $this->stringToTime($value);
            case 'time':
                return $this->stringToDummyTime($value);
            case 'date':
                return $this->valueToDate($value);
            case 'boolean':
                return $this->valueToBoolean($value);
            default:
                return $value;
        }
    }
    
    protected function stringToTime($string)
    {
        if (!is_string($string)) {
            return $string;
        } elseif (!$string) {
            return null;
        }

        return $this->convertStringToTime($string);
    }
    
    protected function stringToDummyTime($string)
    {
        if (!is_string($string)) {
            return $string;
        } elseif (!$string) {
            return null;
        }
        
        $dummyTimeString = '2000-01-01 ' . $string;
        
        return $this->convertStringToTime($dummyTimeString);
    }
    
    protected function valueToDate($value)
    {
        if (is_string($value)) {
            if (!$value) {
                return null;
            }
            return $this->convertStringToTime($value);
        }
        return $value;
    }
    
    protected function valueToBoolean($value)
    {
        if (is_string($value) && !$value) {
            return null;
        } else {
            return in_array($value, self::$TRUE_VALUES);
        }
    }
    
    protected function convertStringToTime($string)
    {
        try {
            if (ctype_digit($string)) {
                return Carbon::createFromTimestamp($string);
            } else {
                return new Carbon($string);
            }
        } catch (\Exception $e) {
            return null;
        }
    }
    
    protected function valueToInteger($value)
    {
        if ($value === true || $value === false) {
            return $value ? 1 : 0;
        } else {
            if (!is_numeric($value)) {
                return null;
            }
            if (is_string($value) && ctype_digit($value) && PHP_INT_MAX < $value) {
                return $value;
            }
            return (int)$value;
        }
    }
    
    protected function extractMetadata($dataType)
    {
        $dataType = str_replace(' ', '', $dataType);
        
        $this->numericScale           = $this->extractScale($dataType);
        $this->characterMaximumLength = $this->extractLimit($dataType);
        $this->numericPrecision       = $this->extractPrecision($dataType);
    }
    
    /**
     * Tinyints columns whose name begins with "is_" (like "is_active") are
     * assumed to be booleans.
     */
    protected function simplifiedDataType($dataType)
    {
        $dataType = strtolower($dataType);
        
        if (is_int(strpos($dataType, 'int'))) {
            if ($dataType == 'tinyint' && strpos($this->name, 'is_') === 0) {
                return 'boolean';
            }
            return 'integer';
        } elseif (
            is_int(strpos($dataType, 'float')) ||
            is_int(strpos($dataType, 'double'))
        ) {
            return 'float';
        } elseif (
            is_int(strpos($dataType, 'decimal')) ||
            is_int(strpos($dataType, 'numeric')) ||
            is_int(strpos($dataType, 'number'))  ||
            is_int(strpos($dataType, 'real'))
        ) {
            if (!$this->numericScale) {
                return 'integer';
            } else {
                return 'float';
            }
        } elseif (is_int(strpos($dataType, 'datetime'))) {
            return 'datetime';
        } elseif (is_int(strpos($dataType, 'timestamp'))) {
            return 'timestamp';
        } elseif (is_int(strpos($dataType, 'time'))) {
            return 'time';
        } elseif (is_int(strpos($dataType, 'date'))) {
            return 'date';
        } elseif (
            is_int(strpos($dataType, 'clob')) ||
            is_int(strpos($dataType, 'text'))
        ) {
            return 'text';
        } elseif (
            is_int(strpos($dataType, 'blob')) ||
            is_int(strpos($dataType, 'binary'))
        ) {
            return 'binary';
        } elseif (
            is_int(strpos($dataType, 'char')) ||
            is_int(strpos($dataType, 'string'))
        ) {
            return 'string';
        } elseif (is_int(strpos($dataType, 'boolean'))) {
            return 'boolean';
        } else {
            return 'string';
        }
    }
    
    protected function extractLimit($dataType)
    {
        if (is_int($pos = strpos($dataType, '('))) {
            return (int)substr($dataType, $pos + 1, -1);
        }
    }
    
    protected function extractPrecision($dataType)
    {
        if (
            is_int(strpos($dataType, 'decimal')) ||
            is_int(strpos($dataType, 'numeric')) ||
            is_int(strpos($dataType, 'number'))  ||
            is_int(strpos($dataType, 'real'))
        ) {
            return $this->extractLimit($dataType);
        }
    }
    
    protected function extractScale($dataType)
    {
        if (is_bool($pos = strpos($dataType, ','))) {
            return 0;
        } else {
            return (int)substr($dataType, $pos + 1, -1);
        }
    }
}
