<?php
namespace Rails\ActiveRecord\Sql\Platform\SqlServer;

use Zend\Db\Sql\Platform\SqlServer\SelectDecorator as BaseDecorator;
use Rails\ActiveRecord\Sql\Platform\SelectDecoratorModifierTrait;

class SelectDecorator extends BaseDecorator
{
    use SelectDecoratorModifierTrait;
}
