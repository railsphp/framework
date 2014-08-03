<?php
namespace Rails\ActiveRecord\Schema;

use Zend\Db\Adapter\Platform\PlatformInterface;
use Zend\Db\Sql\AbstractSql;
use Zend\Db\Sql\Ddl\SqlInterface;

class DropDatabase extends AbstractSql implements SqlInterface
{
    const DATABASE = 'database';

    protected $specifications = array(
        self::DATABASE => 'DROP DATABASE IF EXISTS %1$s'
    );

    /**
     * @var string
     */
    protected $database = '';

    /**
     * @param string $database
     */
    public function __construct($database = '')
    {
        $this->database = $database;
    }

    /**
     * @param  null|PlatformInterface $adapterPlatform
     * @return string
     */
    public function getSqlString(PlatformInterface $adapterPlatform = null)
    {
        $adapterPlatform = ($adapterPlatform) ?: new AdapterSql92Platform;

        $sqls       = array();
        $parameters = array();

        foreach ($this->specifications as $name => $specification) {
            $parameters[$name] = $this->{'process' . $name}(
                $adapterPlatform,
                null,
                null,
                $sqls,
                $parameters
            );

            if ($specification && is_array($parameters[$name])) {
                $sqls[$name] = $this->createSqlFromSpecificationAndParameters(
                    $specification,
                    $parameters[$name]
                );
            }
        }

        $sql = implode(' ', $sqls);
        return $sql;
    }

    protected function processDataBase(PlatformInterface $adapterPlatform = null)
    {
        return array($adapterPlatform->quoteIdentifier($this->database));
    }
}
