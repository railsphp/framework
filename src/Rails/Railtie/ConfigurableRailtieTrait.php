<?php
namespace Rails\Railtie;


trait ConfigurableRailtieTrait
{
    
    protected $config;
    
    public function config()
    {
        if (!$this->config) {
            $this->initConfig();
        }
        return $this->config;
    }
    
    protected function initConfig()
    {
        $this->config = ConfigurableRailtieManager::instance()
                            ->configureRailtie(get_called_class());
    }
}
