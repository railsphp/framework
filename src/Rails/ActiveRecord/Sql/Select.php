<?php
namespace Rails\ActiveRecord\Sql;

use Zend\Db\Adapter\Platform\PlatformInterface;
use Zend\Db\Adapter\Driver\DriverInterface;
use Zend\Db\Adapter\ParameterContainer;
use Zend\Db\Sql\Select as Base;

/**
 * Zend quotes limit and offset currently in 2.3.1 (15/07/14)
 */
class Select extends Base
{
    protected function processLimit(PlatformInterface $platform, DriverInterface $driver = null, ParameterContainer $parameterContainer = null)
    {
        if ($this->limit === null) {
            return null;
        }

        $limit = $this->limit;

        if ($driver) {
            $sql = $driver->formatParameterName('limit');
            $parameterContainer->offsetSet('limit', $limit, ParameterContainer::TYPE_INTEGER);
        } else {
            $sql = (int)$limit;
        }

        return array($sql);
    }

    protected function processOffset(PlatformInterface $platform, DriverInterface $driver = null, ParameterContainer $parameterContainer = null)
    {
        if ($this->offset === null) {
            return null;
        }

        $offset = $this->offset;

        if ($driver) {
            $parameterContainer->offsetSet('offset', $offset, ParameterContainer::TYPE_INTEGER);
            return array($driver->formatParameterName('offset'));
        }

        return array((int)$offset);
    }

}
