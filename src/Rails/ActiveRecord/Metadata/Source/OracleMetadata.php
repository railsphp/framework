<?php
namespace Rails\ActiveRecord\Metadata\Source;

use Zend\Db\Metadata\Source\OracleMetadata as Zf2Base;

/**
 * Extends ZF2's corresponding Metadata object and adds
 * the ColumnModifierTrait.
 */
class OracleMetadata extends Zf2Base
{
    use ColumnModifierTrait;
}
