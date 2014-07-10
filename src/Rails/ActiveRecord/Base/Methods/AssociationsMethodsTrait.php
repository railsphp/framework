<?php
namespace Rails\ActiveRecord\Base\Methods;

use Rails;
use Rails\ActiveRecord\Associations\Exception\TypeMissmatchException;
use Rails\ActiveRecord\Associations\Associations;
use Rails\ActiveRecord\Associations\Setter;
use Rails\ActiveRecord\Exception;
use Rails\ActiveRecord\Base;
use Rails\ActiveRecord\Persistance\Exception\RecordNotSavedException;

trait AssociationsMethodsTrait
{
    /**
     * An array where loaded associations will
     * be stored.
     */
    protected $loadedAssociations = [];
    
    /**
     * If the association isn't yet loaded, it is loaded and returned.
     * If the association doesn't exist, `null` is returned. Note that
     * unset one-to-one associations return `false`.
     *
     * return null|false|object
     */
    public function getAssociation($name)
    {
        if (isset($this->loadedAssociations[$name])) {
            return $this->loadedAssociations[$name];
        } elseif ($this->getAssociations()->exists($name)) {
            $this->loadedAssociations[$name] =
                $this->getAssociations()->load($this, $name);
            return $this->loadedAssociations[$name];
        }
        return null;
    }
    
    /**
     * Associates an object to a one-to-one association.
     * Other associations are done in CollectionProxy.
     */
    public function setAssociation($name, $value)
    {
        return (new Setter())->set($this, $name, $value);
    }
    
    /**
     * Returns the Associations object that holds the associations
     * data for the called class.
     *
     * @return Associations
     */
    public function getAssociations()
    {
        return Associations::forClass(get_called_class());
    }
    
    /**
     * Defines associations.
     * This method may be overriden to define associations for a class.
     * Other methods whose name end in `Associations`, like `postAssociations`,
     * will also be considered when requiring all associations for a class.
     *
     * The name of the class for an association, if not specified, is
     * deduced out of the name of the association:
     *
     * <pre>
     * 'belongsTo' => [
     *     'ownerUser', // Class not specified; would deduce "OwnerUser".
     *     'owner' => [ 'class' => 'User' ] // Class specified.
     * ]
     * </pre>
     *
     *
     * @return array
     */
    protected function associations()
    {
        return [];
    }
}
