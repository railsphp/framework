<?php
namespace Rails\ActiveRecord;

use Closure;
use Rails\ActiveRecord\Base\Methods;
use Rails\ServiceManager\ServiceLocatorAwareTrait;
use Rails\ActiveModel;
use Rails\ActiveModel\Attributes\Attributes;
use Rails\ActiveRecord\Attributes\Attribute;
use Rails\ActiveRecord\Persistance\Exception\RecordNotSavedException;

/**
 * Base SQL-persisted model.
 */
abstract class Base extends Persistence\PersistedModel\PersistedModel
{
    use Methods\ModelSchemaMethodsTrait,
        Methods\AssociationsMethodsTrait;
    
    /**
     * Can be overwritten to set the name of the table to which this
     * model belongs to.
     *
     * @see Base\Methods\ModelSchemaMethods::tableName()
     */
    const TABLE_NAME  = '';
    
    /**
     * @see Base\Methods\ModelSchemaMethods::tableNamePrefix()
     */
    const TABLE_NAME_PREFIX  = '';
    
    /**
     * @see Base\Methods\ModelSchemaMethods::tableNameSuffix()
     */
    const TABLE_NAME_SUFFIX  = '';
    
    /**
     * Array that holds Base\ModelSchema objects for the different models.
     * The schema is retrieved with a model's method static table().
     */
    protected static $modelSchemas = [];
    
    /**
     * Helps selecting the correct adapter for a model.
     */
    protected static $connectionManager;
    
    /**
     * Array of Zend\Db\Metadata\MetadataInterface objects.
     *
     * @var array
     */
    protected static $metadatas = [];
    
    protected static function attributeSet()
    {
        $set = parent::attributeSet();
        
        foreach (static::table()->columnsHash() as $k => $column) {
            $set->addAttribute(
                new Attribute($column)
            );
        }
        return $set;
    }
    
    protected static function initAttributeSet()
    {
        $className = get_called_class();
        
        if (!Attributes::attributesSetFor($className)) {
            Attributes::setClassAttributes(
                $className,
                static::attributeSet()
            );
        }
    }
    
    /**
     * Get the database adapter depending on connectionName().
     *
     * @return \Zend\Db\Adapter\Adapter
     */
    public static function adapter()
    {
        return self::connectionManager()->getAdapter(static::connectionName());
    }
    
    /**
     * Name of the column that is the primary key.
     *
     * @return string
     */
    public static function primaryKey()
    {
        return static::PRIMARY_KEY;
    }
    
    /**
     * Collection object for this model. Can be overwritten to set
     * a custom collection class that extends Collection.
     *
     * @return Collection
     */
    public static function collection(array $members = [])
    {
        return new Collection($members);
    }
    
    /**
     * Run $block wrapped in a transaction. Any exception caught by this
     * try-catch block will issue a rollback, and the exception will be thrown
     * again. To issue a silent rollback, $block may return `false`.
     *
     * @return bool
     * @see Adapter\Driver\Pdo\Connection::transaction()
     */
    public static function transaction(Closure $block, array $options = [])
    {
        $connection = static::adapter()->getDriver()->getConnection();
        $connection->beginTransaction($options);
        
        try {
            if (false !== $block()) {
                $connection->commit();
            } else {
                $connection->rollBack();
                return false;
            }
        } catch (\Exception $e) {
            $connection->rollback();
            throw $e;
        }
        
        return true;
    }
    
    public static function deletedAtType()
    {
        if (static::isRecoverable()) {
            return static::table()->getColumn(static::deletedAtAttribute())->type();
        }
        return false;
    }
    
    /**
     * Relation method.
     */
    public static function includes(/*...$args*/)
    {
        return call_user_func_array([self::all(), 'includes'], func_get_args());
    }
    
    protected static function persistence()
    {
        if (!static::$persistence) {
            static::$persistence = new Persistence\Sql();
        }
        return static::$persistence;
    }
    
    public function __call($method, $params)
    {
        if ($this->getAssociation($method) !== null) {
            return $this->loadedAssociations[$method];
        }
        return parent::__call($method, $params);
    }
    
    public function __set($prop, $value)
    {
        if ($this->getAssociations()->exists($prop)) {
            $this->setAssociation($prop, $value);
            return;
        }
        
        return parent::__set(static::properAttributeName($prop), $value);
    }
    

    
    public function directUpdates(array $attrsValuesPairs)
    {
        try {
            if (self::persistence()->updateColumns($this, $attrsValuesPairs)) {
                $this->getAttributes()->setRaw($attrsValuesPairs);
                return true;
            }
        } catch (RecordNotSavedException $e) {
        }
        return false;
    }
    
    /**
     * Alias of directUpdates().
     */
    public function updateColumns(array $attrsValuesPairs)
    {
        return $this->directUpdates($attrsValuesPairs);
    }
    
    /**
     * Alias of directUpdate().
     */
    public function updateColumn($columnName, $value)
    {
        return $this->directUpdate($columnName, $value);
    }
    
    public function reload()
    {
        $this->loadedAssociations = [];
        return parent::reload();
    }
    
    public function getProperty($name)
    {
        if ($this->getAssociations()->exists($name)) {
            return $this->getAssociations()->load($this, $name);
        }
        return parent::getProperty($name);
    }
    
    protected function createOrUpdate(array $options = [])
    {
        return $this->transaction(function() use ($options) {
            return parent::createOrUpdate($options);
        });
    }
    
    protected function deleteOrDestroy(array $options = [])
    {
        return $this->transaction(function() use ($options) {
            return parent::deleteOrDestroy($options);
        });
    }
    
    protected function defaultAttributes()
    {
        return self::table()->columnDefaults();
    }
}
