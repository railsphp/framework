<?php
namespace Rails\ActiveRecord\Metadata\Source;

use Zend\Db\Metadata\Source\SqliteMetadata as Zf2Base;

/**
 * Extends ZF2's corresponding Metadata object and adds
 * the ColumnModifierTrait.
 */
class SqliteMetadata extends Zf2Base
{
    use ColumnModifierTrait;
}
