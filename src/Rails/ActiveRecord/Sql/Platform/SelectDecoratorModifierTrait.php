<?php
namespace Rails\ActiveRecord\Sql\Platform;

use Zend\Db\Adapter\Platform\PlatformInterface;
use Zend\Db\Adapter\Driver\DriverInterface;
use Zend\Db\Adapter\ParameterContainer;
use Zend\Db\Sql\TableIdentifier;
use Zend\Db\Sql\ExpressionInterface;

/**
 * Modifies ZF's SelectDecorator to hack the processSelect() method, because
 * it forcedly adds any joined table to the $columns array, even if the value
 * is empty or invalid. This trait will allow passing an empty value as join columns,
 * which will cause the columns to not to be added.
 */
trait SelectDecoratorModifierTrait
{
    protected function processSelect(PlatformInterface $platform, DriverInterface $driver = null, ParameterContainer $parameterContainer = null)
    {
        $expr = 1;

        if ($this->table) {
            $table = $this->table;
            $schema = $alias = null;

            if (is_array($table)) {
                $alias = key($this->table);
                $table = current($this->table);
            }

            // create quoted table name to use in columns processing
            if ($table instanceof TableIdentifier) {
                list($table, $schema) = $table->getTableAndSchema();
            }

            if ($table instanceof Select) {
                $table = '(' . $this->processSubselect($table, $platform, $driver, $parameterContainer) . ')';
            } else {
                $table = $platform->quoteIdentifier($table);
            }

            if ($schema) {
                $table = $platform->quoteIdentifier($schema) . $platform->getIdentifierSeparator() . $table;
            }

            if ($alias) {
                $fromTable = $platform->quoteIdentifier($alias);
                $table = $this->renderTable($table, $fromTable);
            } else {
                $fromTable = $table;
            }
        } else {
            $fromTable = '';
        }

        if ($this->prefixColumnsWithTable) {
            $fromTable .= $platform->getIdentifierSeparator();
        } else {
            $fromTable = '';
        }

        // process table columns
        $columns = array();
        foreach ($this->columns as $columnIndexOrAs => $column) {

            $columnName = '';
            if ($column === self::SQL_STAR) {
                $columns[] = array($fromTable . self::SQL_STAR);
                continue;
            }

            if ($column instanceof ExpressionInterface) {
                $columnParts = $this->processExpression(
                    $column,
                    $platform,
                    $driver,
                    $this->processInfo['paramPrefix'] . ((is_string($columnIndexOrAs)) ? $columnIndexOrAs : 'column')
                );
                if ($parameterContainer) {
                    $parameterContainer->merge($columnParts->getParameterContainer());
                }
                $columnName .= $columnParts->getSql();
            } else {
                $columnName .= $fromTable . $platform->quoteIdentifier($column);
            }

            // process As portion
            if (is_string($columnIndexOrAs)) {
                $columnAs = $platform->quoteIdentifier($columnIndexOrAs);
            } elseif (stripos($columnName, ' as ') === false) {
                $columnAs = (is_string($column)) ? $platform->quoteIdentifier($column) : 'Expression' . $expr++;
            }
            $columns[] = (isset($columnAs)) ? array($columnName, $columnAs) : array($columnName);
        }

        $separator = $platform->getIdentifierSeparator();

        // process join columns
        foreach ($this->joins as $join) {
            foreach ($join['columns'] as $jKey => $jColumn) {
                if (!$jColumn) {
                    continue;
                }
                
                $jColumns = array();
                if ($jColumn instanceof ExpressionInterface) {
                    $jColumnParts = $this->processExpression(
                        $jColumn,
                        $platform,
                        $driver,
                        $this->processInfo['paramPrefix'] . ((is_string($jKey)) ? $jKey : 'column')
                    );
                    if ($parameterContainer) {
                        $parameterContainer->merge($jColumnParts->getParameterContainer());
                    }
                    $jColumns[] = $jColumnParts->getSql();
                } else {
                    $name = (is_array($join['name'])) ? key($join['name']) : $name = $join['name'];
                    if ($name instanceof TableIdentifier) {
                        $name = ($name->hasSchema() ? $platform->quoteIdentifier($name->getSchema()) . $separator : '') . $platform->quoteIdentifier($name->getTable());
                    } else {
                        $name = $platform->quoteIdentifier($name);
                    }
                    $jColumns[] = $name . $separator . $platform->quoteIdentifierInFragment($jColumn);
                }
                if (is_string($jKey)) {
                    $jColumns[] = $platform->quoteIdentifier($jKey);
                } elseif ($jColumn !== self::SQL_STAR) {
                    $jColumns[] = $platform->quoteIdentifier($jColumn);
                }
                $columns[] = $jColumns;
            }
        }

        if ($this->quantifier) {
            if ($this->quantifier instanceof ExpressionInterface) {
                $quantifierParts = $this->processExpression($this->quantifier, $platform, $driver, 'quantifier');
                if ($parameterContainer) {
                    $parameterContainer->merge($quantifierParts->getParameterContainer());
                }
                $quantifier = $quantifierParts->getSql();
            } else {
                $quantifier = $this->quantifier;
            }
        }

        if (!isset($table)) {
            return array($columns);
        } elseif (isset($quantifier)) {
            return array($quantifier, $columns, $table);
        } else {
            return array($columns, $table);
        }
    }
}
