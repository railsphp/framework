<?php
namespace Rails\ActiveRecord\Sql\Platform\Mysql;

use Zend\Db\Sql\Platform\Mysql\SelectDecorator as BaseDecorator;
use Rails\ActiveRecord\Sql\Platform\SelectDecoratorModifierTrait;

class SelectDecorator extends BaseDecorator
{
    use SelectDecoratorModifierTrait;
}
