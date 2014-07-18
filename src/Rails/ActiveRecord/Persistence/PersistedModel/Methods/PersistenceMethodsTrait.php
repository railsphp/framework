<?php
namespace Rails\ActiveRecord\Persistence\PersistedModel\Methods;

// use Zend\Db\Adapter\Adapter;
// use Zend\Db\Sql;
use Closure;
use Rails\ActiveModel\Attributes\Attributes;
use Rails\ActiveRecord\Exception;
use Rails\ActiveRecord\Persistence;
use Rails\ActiveRecord\Persistence\Exception\RecordNotSavedException;
use Rails\ActiveRecord\Persistence\Exception\RecordNotDestroyedException;

/**
 * @uses \Rails\ActiveRecord\Base::$persistence
 */
trait PersistenceMethodsTrait
{
    /**
     * Is new record?
     * Flag to identify whether this record is persisted or not.
     *
     * @var bool
     */
    protected $isNewRecord = false;
    
    /**
     * Is record destroyed?
     * Flag to identify whether this record is destroyed or not.
     *
     * @var bool
     */
    protected $isDestroyed = false;
    
    
    /**
     * Updates attributes directly in the database, without calling
     * any callbacks or validations. The `updated_at`/`updated_on` columns
     * aren't changed either.
     *
     * @param array $attrsValuesPairs
     * @return bool
     */
    abstract public function directUpdates(array $attrsValuesPairs);
    
    protected static function persistence()
    {
        throw new \RuntimeException(
            __METHOD__ . " static method must be overriden"
        );
    }
    
    /**
     * pass [ ['name' => ...], ['name' => ... ] ] as $attrs to create multiple
     * records.
     */
    public static function create($attributes = null, Closure $block = null)
    {
        if ($attributes instanceof \Closure) {
            $block = $attributes;
            $attributes = [];
        } elseif (!is_array($attributes)) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    'First argument must be either array or Closure, %s passed',
                    gettype($attributes)
                )
            );
        }
        
        if (isset($attributes[0])) {
            $coll = static::collection();
            foreach ($attributes as $attrs) {
                $coll->add(static::create($attrs, $block));
            }
            return $coll;
        } else {
            $model = new static();
            $model->assignAttributes($attributes);
            if ($block) {
                $block($model);
            }
            $model->save();
            return $model;
        }
    }
    
    /**
     * Classes that have the DELETED_AT_ATTRIBUTE as one of their attributes
     * are considered recoverable. Setting IS_RECOVERABLE to false will
     * disable this.
     *
     * @return bool
     */
    public static function isRecoverable()
    {
        return static::IS_RECOVERABLE &&
            static::getAttributeSet()->exists(static::DELETED_AT_ATTRIBUTE);
    }
    
    public function isNewRecord()
    {
        return $this->isNewRecord;
    }
    
    public function isDestroyed()
    {
        return $this->isDestroyed;
    }
    
    /**
     * Checks if record is soft-deleted. This is true if the record is
     * recoverable and it is deleted.
     * Note that this would return `false` if called on a recoverable
     * object that was destroyed without being deleted.
     *
     * @return bool
     */
    public function isDeleted()
    {
        return $this->isRecoverable() && $this->deletedAt();
    }
    
    public function isPersisted()
    {
        return !($this->isNewRecord || $this->isDestroyed);
    }
    
    public function save(array $options = [])
    {
        if (empty($options['exception'])) {
            try {
                return $this->createOrUpdate($options);
            } catch (Exception\RecordInvalidException $e) {
                return false;
            } catch (RecordNotSavedException $e) {
                return false;
            }
        } else {
            return $this->createOrUpdate($options);
        }
    }
    
    /**
     * Pass true as the `exception` option to make the process strict: if the record
     * isn't destroyed, `RecordNotDestroyedException` is thrown.
     * Calling `destroy` on a recoverable object will soft-delete the record.
     * That is, the `deleted_at` attribute will be filled with the current date-time
     * and the record won't be removed from the database. Calling `destroy` on a
     * soft-deleted record won't do anything. To actually destroy the record, you
     * can either call `hardDestroy()` or pass the `hardDestroy` option to `destroy()`.
     *
     * @return bool
     * @throws RecordNotDestroyedException
     * @see hardDestroy()
     */
    public function destroy(array $options = [])
    {
        if (empty($options['exception'])) {
            try {
                return $this->deleteOrDestroy($options);
            } catch (RecordNotDestroyedException $e) {
                return false;
            }
        } else {
            return $this->deleteOrDestroy($options);
        }
    }
    
    /**
     * Calling hardDestroy on a non-recoverable object will ignore the miscall
     * and simply destroy the record.
     * This is the same as passing the `hardDestroy` option to to the `destroy` method.
     *
     * @return bool
     */
    public function hardDestroy(array $options = [])
    {
        $options['hardDestroy'] = true;
        return $this->destroy($options);
    }
    
    /**
     * Reloads data from database and resets all changes. `find()` is used to get the record;
     * if it's not found, RecordNotFoundException is thrown.
     *
     * @return self
     */
    public function reload()
    {
        $freshObject = static::find($this->id());
        $this->getAttributes()->dirty()->resetChanges();
        $this->getAttributes()->setRaw($freshObject->attributes());
        return $this;
    }
    
    /**
     * Recovers a deleted object by setting the DELETED_AT_ATTRIBUTE to null.
     * If the object is not deleted, no action is taken.
     * If the object isn't recoverable, however, an exception is thrown.
     * `recover` callbacks are ran if the record is recovered. Note that the
     * attribute is directly updated in the database, so no `save` or `update`
     * callbacks are ran. Also, any change made to the model is discarted as it is
     * reloaded.
     *
     * @return bool
     * @throws BadMethodCallException
     */
    public function recover()
    {
        if (!$this->isRecoverable()) {
            throw new Exception\BadMethodCallException(
                sprintf(
                    "Object of class %s isn't recoverable",
                    get_class($this)
                )
            );
        }
        
        if ($this->isDeleted()) {
            $this->runCallbacks('recover', function() {
                $this->directUpdate(static::deletedAtAttribute(), static::deletedAtEmptyValue());
                $this->reload();
            });
        }
        
        return true;
    }
    
    public function updateAttribute($attrName, $value)
    {
        return $this->updateAttributes([$attrName => $value]);
    }
    
    public function updateAttributes(array $attributes)
    {
        $this->assignAttributes($attributes);
        return $this->save();
    }
    
    /**
     * Same as calling `directUpdates([$attrName => $value])`.
     *
     * @return bool
     * @see directUpdates()
     */
    public function directUpdate($attrName, $value)
    {
        return $this->directUpdates([$attrName => $value]);
    }
    
    # TODO otros métodos
    
    protected function reloadModel($persistedAttrs)
    {
        if (!$persistedAttrs) {
            throw new Exception\RecordNotFoundException(
                ""
            );
        }
        $this->getAttributes()->dirty()->changesApplied();
    }
    
    protected function createOrUpdate(array $options = [])
    {
        if ($this->isNewRecord()) {
            return $this->createRecord($options);
        } else {
            return $this->updateRecord($options);
        }
    }
    
    protected function createRecord(array $options = [])
    {
        if (!$this->runCallbacks('validation', function() {
                return $this->isValid('create');
            })
        ) {
            return false;
        }
        
        if ($this->runCallbacks('save', function() {
            return $this->runCallbacks('create', function() {
                $id = static::persistence()->insert($this);
                
                if (!Attributes::isClassAttribute(get_called_class(), static::primaryKey())) {
                    if ($id) {
                        $this->isNewRecord = false;
                        return true;
                    }
                } elseif ($id) {
                    $this->setAttribute(static::primaryKey(), $id);
                    $this->isNewRecord = false;
                    return true;
                }
                return false;
            });
        })) {
            # TODO: after-commit callbacks.
            // $this->runCallbacks('commit');
            return true;
        }
        return false;
    }
    
    protected function updateRecord(array $options = [])
    {
        if (!$this->hasChanged()) {
            # Nothing to update.
            return true;
        }
        
        if (!$this->runCallbacks('validation', function() {
                return $this->isValid('update');
            })
        ) {
            return false;
        }
        
        if ($this->runCallbacks('save', function() {
            static::persistence()->update($this);
        })) {
            # TODO: after-commit callbacks.
            // $this->runCallbacks('commit');
            return true;
        }
    }
    
    protected function deleteOrDestroy(array $options = [])
    {
        if ($this->isRecoverable() && empty($options['hardDestroy'])) {
            return $this->deleteRecord($options);
        } else {
            return $this->destroyRecord($options);
        }
    }
    
    /**
     * Soft-deletes the record. If the record is already deleted, no action is taken.
     * The `delete` callbacks are ran if the record is deleted. Like in `recover`, the
     * attribute is directly updated in the database, so no `save` or `update`
     * callbacks are ran, and any change made to the model is discarted as it is
     * reloaded.
     *
     * @return bool
     */
    protected function deleteRecord(array $options = [])
    {
        $this->runCallbacks('delete', function() {
            if (!$this->deletedAt()) {
                $this->directUpdate(static::deletedAtAttribute(), static::deletedAtValue());
                $this->reload();
            }
        });
        return true;
    }
    
    /**
     * Removes the record from the database. The `destroy` callbacks are ran.
     *
     * @return bool
     */
    protected function destroyRecord(array $options = [])
    {
        return $this->runCallbacks('destroy', function() {
            if (static::persistence()->delete($this)) {
                $this->isDestroyed = true;
                return true;
            }
            return false;
        });
    }
}
