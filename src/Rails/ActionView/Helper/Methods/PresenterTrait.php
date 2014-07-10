<?php
namespace Rails\ActionView\Helper\Methods;

use Rails\ActionView\Presenter;

trait PresenterTrait
{
    /**
     * Current presenter instance
     *
     * @var Presenter
     */
    protected $presenter;
    
    /**
     * Presenter instances
     *
     * @var array
     */
    protected $presenters = [];
    
    /**
     * <?= $this->present($this->user)->name() ?>
     * <?= $this->present()->avatar() ?>
     */
    public function present($object = null)
    {
        if ($object) {
            if (is_string($object)) {
                return $this->presenter->$object();
            } else {
                $className = get_class($object) . 'Presenter';
                $this->setPresenter($this->getPresenter($className))->setObject($object);
            }
        }
        return $this->presenter;
    }
    
    public function presenter()
    {
        return $this->presenter;
    }
    
    /**
     * Manullay set a Presenter
     *
     * @param string|Presenter $presenter
     * @return Presenter
     */
    public function setPresenter($presenter)
    {
        if (is_string($presenter)) {
            $this->presenter = $this->getPresenter($presenter);
        } else {
            $this->presenter = $presenter;
        }
        return $this->presenter;
    }
    
    /**
     * Get presenter
     * Checks if instance of $className exists in $presenters,
     * initiates a new one if not, and returns the instance.
     *
     * @return Presenter
     */
    protected function getPresenter($className)
    {
        if (!isset($this->presenters[$className])) {
            $this->presenters[$className] = new $className($this->helperSet);
        }
        return $this->presenters[$className];
    }
}
