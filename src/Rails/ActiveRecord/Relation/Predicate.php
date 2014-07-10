<?php
namespace Rails\ActiveRecord\Relation;

use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Predicate as ZfPredicate;
use Zend\Db\Sql\Predicate\PredicateSet;
use Rails\ActiveRecord\Relation;

abstract class Predicate
{
    /**
     * The type of this predicate, where or having.
     * Extending classes must declare this property.
     */
    protected $type;
    
    protected $relation;
    
    protected $nestings = [];
    
    protected $nestedOperators = [];
    
    protected $defaultOperator = null;
    
    public function __construct(AbstractRelation $relation)
    {
        $this->relation = $relation;
    }
    
    /**
     * Can't define methods with names "and" or "or", although they
     * can be called. So the actual method names are "wAnd" and "wOr",
     * and __call will help to overload them.
     * Any other unknown method will be delegated to $relation after calling
     * `reset()`.
     */
    public function __call($method, $params)
    {
        switch ($method) {
            case 'and':
                return call_user_func_array([$this, 'wAnd'], $params);
            case 'or':
                return call_user_func_array([$this, 'wOr'], $params);
        }
        
        // if (in_array($method, self::$RELATION_METHODS)) {
        // /**
         // * Calling a method that belongs to Relation will cause to
         // * reset $defaultOperator and clear all nestings.
         // */
        $this->reset();
        return call_user_func_array([$this->relation, $method], $params);
        // }
        
        // throw new Exception\BadMethodCallException(
            // sprintf("Called unknown method %s::%s", __CLASS__, $method)
        // );
    }
    
    # TODO: Check if this is possible:
    # $query->where('user_id = :userId OR creator_id = :userId', ['userId' => $userId]);
    /**
     * Adds a condition.
     *   $predicate->condition('id = 15');
     *   $predicate->condition('id = ? AND name = ?', $id, $name);
     *   $predicate->condition(['id' => $id, 'name' => $name]);
     * Or mixed:
     *   $predicate->condition([
     *       // -> "id = 1"
     *       'id' => $id,
     *       
     *       // In this case the placeholders must be manually typed and must
     *       // fit the number or options passed, otherwise an exception is thrown.
     *       // -> "name IN ('foo', 'bar', 'baz')"
     *       'name IN (?, ?, ?)' => ["foo", "bar", "baz"],
     *       
     *       // In this case, the IN predicate is automatically created.
     *       // -> "foo IN (1, 2, 3)"
     *       'address' => [1, 2, 3],
     *       
     *       // -> "ISNULL(phone)"
     *       'phone' => null,
     *       
     *       // In the following, no key is passed, only the value.
     *
     *       // -> "country = ''"
     *       'country = ?',
     *
     *       // -> "name = 15"
     *       'name = 15',
     *
     *       // -> "address", as in "... WHERE id = 15 AND address"
     *       'address'
     *   ]);
     * Note the 'foo' key whose value is an array. It will be converted to "IN (1, 2, 3)".
     */
    public function condition()
    {
        $op = $this->getDefaultOperator();
        $params = func_get_args();
        
        switch (count($params)) {
            case 1:
                if (is_string($params[0])) {
                    $this->addCondition('literal', [$params[0]]);
                } elseif (is_array($params[0])) {
                    $combination = $this->getDefaultOperator();
                    
                    /**
                     * The code within the foreach is an extraction of Zend\Db\Sql\Select::where()
                     */
                    foreach ($params[0] as $pkey => $pvalue) {
                        if (is_string($pkey) && strpos($pkey, '?') !== false) {
                            # ['id = ?' => 1]
                            # ['id IN (?, ?, ?)' => [1, 3, 4]]
                            $predicate = new ZfPredicate\Expression($pkey, $pvalue);
                        } elseif (is_string($pkey)) {
                            if ($pvalue === null) {
                                # ['name' => null] -> "ISNULL(name)"
                                $predicate = new ZfPredicate\IsNull($pkey, $pvalue);
                            } elseif (is_array($pvalue)) {
                                # ['id' => [1, 2, 3]] -> "id IN (1, 2, 3)"
                                $predicate = new ZfPredicate\In($pkey, $pvalue);
                            } else {
                                # ['id' => $id] -> "id = 15"
                                $predicate = new ZfPredicate\Operator($pkey, ZfPredicate\Operator::OP_EQ, $pvalue);
                            }
                        } elseif ($pvalue instanceof ZfPredicate\PredicateInterface) {
                            $predicate = $pvalue;
                        } else {
                            # ['id = ?'] -> "id = ''"
                            # ['id = 1'] -> literal.
                            $predicate = (strpos($pvalue, Expression::PLACEHOLDER) !== false)
                                ? new ZfPredicate\Expression($pvalue) : new ZfPredicate\Literal($pvalue);
                        }
                        $this->getCurrentPredicate()->addPredicate($predicate, $combination);
                    }
                }
                break;
            
            case 2:
                # $rel->where('foo = ? AND id IN (?) AND bar = ?', [true, [1, 2, 3], false]);
                # If passing a non-array value:
                # $rel->where('foo = ?', 1);
                # But if an array is to be passed, must be inside another array
                # $rel->where('id IN (?)', [[1, 2, 3]]);
                if (!is_array($params[1])) {
                    $params[1] = [$params[1]];
                }
                $this->expandPlaceholders($params[0], $params[1]);
                $this->addCondition('expression', [$params[0], $params[1]]);
                break;
            
            case 3:
                # $rel->where('id', '>', 2);
                $this->getCurrentPredicate()->addPredicate(
                     new ZfPredicate\Operator($params[0], $params[1], $params[2])
                );
                break;
            
            default:
                throw new Exception\BadMethodCallException(
                    sprintf(
                        "One to three arguments are expected, %s passed",
                        count($params)
                    )
                );
        }
        
        return $this;
    }
    
    /**
     * Begings a new nesting.
     */
    public function nest()
    {
        $target = $this->getCurrentPredicate();
        
        if ($operator = $this->getDefaultOperator()) {
            $target->$operator;
        }
        
        $this->nestings[] = $target->nest();
        $this->nestedOperators[] = $this->getDefaultOperator();
        
        return $this;
    }
    
    /**
     * Ends last nesting.
     */
    public function endNest()
    {
        array_pop($this->nestings);
        array_pop($this->nestedOperators);
        return $this;
    }
    
    /**
     * If no parameters are passed, begins an "and" block, which means
     * that all preceding conditions (equal, lessThan, etc) will be combined
     * with the AND operator. To end or switch the block you can explicitely call endAnd()
     * or directly call wOr().
     * If parameters are passed (the same way they can be passed to condition())
     * they are added with the AND operator.
     * Note that the default operator is AND, so there could be no need to call this.
     * Can't name a function "and", thus the leading "w".
     *
     * @see condition()
     * @see wOr()
     */
    public function wAnd()
    {
        $this->setDefaultOperator(PredicateSet::OP_AND);
        
        if (func_num_args()) {
            call_user_func_array([$this, 'condition'], func_get_args());
            $this->endAnd();
        }
        
        return $this;
    }
    
    /**
     * Ends an "and" block.
     */
    public function endAnd()
    {
        $this->setDefaultOperator(null);
        return $this;
    }
    
    /**
     * Same as wAnd.
     *
     * @see wAnd()
     */
    public function wOr()
    {
        $this->setDefaultOperator(PredicateSet::OP_OR);
        if (func_num_args()) {
            call_user_func_array([$this, 'condition'], func_get_args());
            $this->endOr();
        }
        
        return $this;
    }
    
    /**
     * Ends an "or" block.
     */
    public function endOr()
    {
        $this->setDefaultOperator(null);
        return $this;
    }
    
    public function equal()
    {
        $this->addCondition('equalTo', func_get_args());
        return $this;
    }
    
    public function not()
    {
        $this->addCondition('notEqualTo', func_get_args());
        return $this;
    }
    
    public function lessThan()
    {
        $this->addCondition('lessThan', func_get_args());
        return $this;
    }
    
    public function greaterThan()
    {
        $this->addCondition('greaterThan', func_get_args());
        return $this;
    }
    
    public function equalOrLess()
    {
        $this->addCondition('lessThanOrEqualTo', func_get_args());
        return $this;
    }
    
    public function equalOrGreater()
    {
        $this->addCondition('greaterThanOrEqualTo', func_get_args());
        return $this;
    }
    
    public function like()
    {
        $this->addCondition('like', func_get_args());
        return $this;
    }
    
    public function notLike()
    {
        $this->addCondition('notLike', func_get_args());
        return $this;
    }
    
    public function isNull()
    {
        $this->addCondition('isNull', func_get_args());
        return $this;
    }
    
    public function notNull()
    {
        $this->addCondition('isNotNull', func_get_args());
        return $this;
    }
    
    public function in()
    {
        $this->addCondition('in', func_get_args());
        return $this;
    }
    
    public function notIn()
    {
        $this->addCondition('notIn', func_get_args());
        return $this;
    }
    
    public function between()
    {
        $this->addCondition('between', func_get_args());
        return $this;
    }
    
    /**
     * Explicity ends a where|having block, returning the relation.
     * An usage scenario would be:
     *   $rel
     *       # Get the Where object
     *       ->where()
     *           # Begin nesting
     *           ->nest()
     *               # No parameters passed to And; begins AND combination
     *               ->and()
     *                   ->greaterThan('id', 3)->lessThan('id', 5)
     *               # Ends AND combination
     *               ->endAnd()
     *           # End nesting
     *           ->endNest()
     *       # Get the original Relation object
     *       ->end()
     *   # Get the first match
     *   ->first();
     *
     * In the case above, however, it's not needed to call end() because calling
     * a method that belongs to the Relation class (in the example above it's
     * first()) will be executed normally.
     *
     * @return Relation
     * @see __call()
     * @see reset()
     */
    public function end()
    {
        $this->reset();
        return $this->relation;
    }
    
    /**
     * Resets the state of the predicate: all nestings and operators are cleared.
     * It would be useful if you're working with the predicate object like this:
     *   // Get the predicate in a separate variable.
     *   $where = $rel->where();
     *   // Do stuff
     *   $where->nest()->...;
     *   // Do other stuff and then get back to the work with $where.
     *   // You would like to call reset() either here or above.
     *   $where->reset()->...;
     *
     * @return Predicate
     */
    public function reset()
    {
        $this->nestings = [];
        $this->nestedOperators = [];
        $this->defaultOperator = null;
        return $this;
    }
    
    protected function inNest()
    {
        return (bool)$this->nestings;
    }
    
    protected function setDefaultOperator($op)
    {
        if ($this->inNest()) {
            end($this->nestedOperators);
            $key = key($this->nestedOperators);
            $this->nestedOperators[$key] = $op;
        } else {
            $this->defaultOperator = $op;
        }
    }
    
    protected function getDefaultOperator()
    {
        if ($this->inNest()) {
            return end($this->nestedOperators);
        } else {
            return $this->defaultOperator;
        }
    }
    
    protected function getPredicate()
    {
        return $this->relation->getSelect()->{$this->type};
    }
    
    protected function getCurrentPredicate()
    {
        if ($this->nestings) {
            return end($this->nestings);
        } else {
            return $this->getPredicate();
        }
    }
    
    /**
     * Actually adds the condition to the proper target.
     */
    protected function addCondition($method, array $params)
    {
        $target = $this->getCurrentPredicate();
        
        # Set default operator if any.
        if ($operator = $this->getDefaultOperator()) {
            $target->$operator;
        }
        
        call_user_func_array([$target, $method], $params);
    }
    
    /**
     * Expand placeholders.
     * If one of the parameters is an array, its corresponding '?' placeholder
     * is "expanded" by the amount of elements the array contains.
     * The $params array is flatten.
     * <pre>
     * ['foo IN (?)', [1, 2, 3]] -> 'foo IN (?, ?, ?)'
     * </pre>
     */
    protected function expandPlaceholders(&$expression, array &$params)
    {
        if (!$params || false === strpos($expression, '?')) {
            return;
        }
        
        $convert = false;
        
        foreach ($params as $param) {
            if (is_array($param)) {
                $convert = true;
                break;
            }
        }
        
        if (!$convert) {
            return;
        }
        
        $flatParams = [];
        $parts      = explode('?', $expression);
        
        foreach ($parts as $i => &$part) {
            if (isset($params[$i])) {
                if (is_array($params[$i])) {
                    $part .= implode(
                        ', ',
                        array_fill(0, count($params[$i]), '?')
                    );
                    $flatParams = array_merge($flatParams, $params[$i]);
                } else {
                    $part .= '?';
                    $flatParams[] = $params[$i];
                }
            }
        }
        
        $expression = implode('', $parts);
        $params     = $flatParams;
    }
}
