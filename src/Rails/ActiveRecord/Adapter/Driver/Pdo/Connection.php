<?php
namespace Rails\ActiveRecord\Adapter\Driver\Pdo;

use Zend\Db\Adapter\Driver\Pdo\Connection as Base;
use Rails\ActiveRecord\Adapter\Driver\ConnectionModifierTrait;

class Connection extends Base
{
    use ConnectionModifierTrait;
    
    protected static $NESTABLE_DRIVERS = [
        'Pgsql',    'Mysql',
        'Mysqli',   'Sqlite'
    ];
    
    /**
     * Transaction nest level.
     *
     * @var int
     */
    protected $transactionLevel = 0;
    
    protected $transactions = [];
    
    /**
     * Whether the driver supports savepoints or not.
     * The proper value is set by `isNestable()`.
     *
     * @var bool
     */
    protected $isNestable;
    
    /**
     * Extended to set error mode to silent.
     */
    public function connect()
    {
        parent::connect();
        $this->resource->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);
        return $this;
    }

    public function inTransaction()
    {
        return (bool)$this->transactions;
    }
    
    /**
     * The following code is an adaptation of:
     * https://gist.github.com/neoascetic/5269127
     */
    
    /**
     * @return int
     */
    public function transactionLevel()
    {
        return count($this->transactions);
    }

    /**
     * By default, a single transaction block is created even in nested transaction
     * calls. In order to create an actual nested transaction (that is, a savepoint),
     * pass `['new' => true]` as options.
     *
     * @param array $options
     * @return void
     */
    public function beginTransaction(array $options = [])
    {
        if (!$this->isConnected()) {
            $this->connect();
        }
        
        if (!$this->inTransaction() || !empty($options['new'])) {
            $this->workSavepoint(
                'beginTransaction',
                'SAVEPOINT ' . $this->savePointName()
            );
            $this->transactions[] = true;
        } else {
            $this->transactions[] = false;
        }
    }
 
    public function commit()
    {
        if (!$this->isConnected()) {
            $this->connect();
        }
        
        if (array_pop($this->transactions)) {
            $this->workSavepoint(
                'commit',
                'RELEASE SAVEPOINT ' . $this->savePointName()
            );
        }
    }
 
    public function rollback()
    {
        if (!$this->isConnected()) {
            $this->connect();
        }
        
        if (array_pop($this->transactions)) {
            $this->workSavepoint(
                'rollBack',
                'ROLLBACK TO SAVEPOINT ' . $this->savePointName()
            );
        }
    }

    public function getDriverName()
    {
        if (!$this->isConnected()) {
            $this->connect();
        }
        return ucfirst(
            $this->resource->getAttribute(\PDO::ATTR_DRIVER_NAME)
        );
    }
    
    protected function workSavepoint($methodName, $spSql)
    {
        if (
            !$this->transactions ||
            !$this->isNestable()
        ) {
            $this->resource->{$methodName}();
        } else {
            $this->resource->exec($spSql);
        }
    }
 
    protected function isNestable()
    {
        if ($this->isNestable === null) {
            $this->isNestable = in_array(
                $this->getDriverName(),
                static::$NESTABLE_DRIVERS
            );
        }
        
        return $this->isNestable;
    }
    
    protected function savePointName()
    {
        return 'active_record_' . $this->transactionLevel();
    }
}
