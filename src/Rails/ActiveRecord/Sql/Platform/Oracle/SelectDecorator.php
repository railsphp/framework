<?php
namespace Rails\ActiveRecord\Sql\Platform\Oracle;

use Zend\Db\Sql\Platform\Oracle\SelectDecorator as BaseDecorator;
use Rails\ActiveRecord\Sql\Platform\SelectDecoratorModifierTrait;

class SelectDecorator extends BaseDecorator
{
    use SelectDecoratorModifierTrait;
}
