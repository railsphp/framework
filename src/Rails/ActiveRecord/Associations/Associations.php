<?php
namespace Rails\ActiveRecord\Associations;

use Rails\ServiceManager\ServiceLocatorAwareTrait;

class Associations
{
    use ServiceLocatorAwareTrait;
    
    protected static $associationsByClass = [];
    
    /**
     * @var Loader
     */
    protected static $loader;
    
    /**
     * Associations name => options pairs.
     *
     * @var array
     */
    protected $associations = [];
    
    protected $className = [];
    
    public static function forClass($className)
    {
        if (!isset(self::$associationsByClass[$className])) {
            self::$associationsByClass[$className] = new self($className);
        }
        return self::$associationsByClass[$className];
    }
    
    public function __construct($className)
    {
        // $this->model = $model;
        // $this->className = get_class($model);
        $this->className = $className;
    }
    
    public function loader()
    {
        if (!self::$loader) {
            self::$loader = new Loader();
        }
        return self::$loader;
    }
    
    /**
     * Get all associations and their options.
     *
     * @return array
     */
    public function associations()
    {
        if (!$this->associations) {
            if ($this->getService('rails.config')['use_cache']) {
                $this->associations = $this->getCachedData();
            } else {
                $this->associations = $this->getAssociationsData();
            }
        }
        return $this->associations;
    }
    
    /**
     * Return options for one association. If it doesn't exists,
     * `false` is returned.
     *
     * @return array|false
     */
    public function get($name)
    {
        if ($this->exists($name)) {
            return $this->associations[$name];
        }
        return false;
    }
    
    /**
     * Checks if an association exists.
     *
     * @return bool
     */
    public function exists($name)
    {
        return array_key_exists($name, $this->associations());
    }
    
    public function load($record, $name)
    {
        if (!$this->exists($name)) {
            throw new Exception\InvalidArgumentException(
                sprintf("Trying to load unknown association %s", $name)
            );
        }
        
        $options = $this->get($name);
        
        return $this->loader()->load($record, $name, $options);
    }
    
    /**
     * If any of the associations has a 'dependent' option, the
     * before-destroy callback 'alterDependencies' should be called.
     * That method will take care of executing the proper tasks with
     * the dependencies according to the options set.
     * Same with the 'autosave' option.
     *
     * # TODO: Move this info somewhere else?
     * The children's 'autosave' option will override the parent's. For example, User has
     * many posts with 'autosave' => true, and Post belongs to User with 'autosave' => false.
     * Upon User save, although User set autosave => true for its association, since Post set
     * autosave to false, the posts won't be automatically saved. This also clarifies that
     * the 'autosave' option in a child doesn't mean that the parent will be autosaved, rather,
     * it defines if the child itself will be saved on parent's save.
     * If the children are set to be saved alongside its parent, and one of the fails to be saved,
     * the parent will also stay unsaved.
     *
     // * If the children aren't set to be saved, their attributes, however, are 'reset':
     *
     // * <pre>
     // * // User has many posts, autosave: false
     // * $user = User::find(1);
     // * $user->posts()[0]->title = 'Some new title'; // Set new title to first post
     // * $user->name = 'Some new name'; // Edit user so it can be saved
     // * $user->save(); // User saved successfuly
     // * $user->posts()[0]->title = 'Old title'; // Posts attributes got reset
     // * </pre>
     *
     * @return array
     */
    public function callbacks()
    {
        $dependent = false;
        $autosave  = false;
        $callbacks = [
            'beforeDestroy' => [],
            'beforeSave'    => []
        ];
        
        foreach ($this->associations() as $data) {
            switch ($data['type']) {
                case 'belongsTo':
                case 'hasMany':
                case 'hasOne':
                    if (
                        !empty($data['dependent']) &&
                        !$dependent
                    ) {
                        $callbacks = [
                            'beforeDestroy' => [
                                'alterDependencies'
                            ]
                        ];
                    }
                    # Fallthrough
                
                default:
                    if (
                        !empty($data['autosave']) &&
                        !$autosave
                    ) {
                        $callbacks = [
                            'beforeSave' => [
                                'saveDependencies'
                            ]
                        ];
                    }
                    break;
            }
            
            if ($autosave && $dependent) {
                break;
            }
        }
        
        return array_filter($callbacks);
    }
    
    protected function getCachedData()
    {
        $key = 'rails.ar.' . $this->className . '.associations';
        
        return $this->getService('rails.cache')->fetch($key, function() {
            return $this->getAssociationsData();
        });
    }
    
    protected function getAssociationsData()
    {
        $extractor = new Extractor();
        return $extractor->getAssociations($this->className);
    }
}
