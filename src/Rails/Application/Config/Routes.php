<?php
namespace Rails\Application\Config;

class Routes
{
    public static function railsInfoRoute()
    {
        return [
            'constraints' =>  [
                'action' => '/properties|routes/',
            ],
            'requirements' => [],
            'options' => [
                'format' => false,
                'constraints' => [
                    'action' => '/properties|routes/',
                ],
                'alias' => 'rails_info',
                'via' => ['get'],
                'anchor' => true,
                'as' => null,
            ],
            'scope' => [],
            'segmentKeys' => ['action'],
            'isBuilt' => true,
            'path' => '/rails/info(/:action)',
            'name' => 'rails_info',
            'to' => null,
            'controller' => null,
            'action' => null,
            'endPoint' => 'Rails\Application\Info\Dispatcher::dispatchRequest',
            'pathRegex' => '/^\/rails\/info(?:\/(properties|routes))?$/',
            'via' => ['get'],
            'defaults' => [
                'format' => false,
                'alias' => 'rails_info',
                'action' => null
            ],
            'vars' => [
                'action' => 
                [
                    'type' => ':',
                    'constraint' => '/properties|routes/',
                ]
            ],
            'namespaces' => [],
            'requiredVars' => [],
            'optionalGroups' => [
                [
                    'part' => '(/:action)',
                    ['action'],
                ],
            ],
            'anchor' => true,
        ];
    }
    
    public static function assetsRoute($assetsPrefix)
    {
        return [
            'constraints'   => [],
            'requirements'  => [],
            'options' => [
                'format'    => false,
                'via'       => ['get'],
                'anchor'    => true,
                'as'        => null,
                'alias'     => null,
            ],
            'scope'         => [],
            'segmentKeys'   => null,
            'isBuilt'       => true,
            'path'          => $assetsPrefix . '/*path',
            'name'          => null,
            'to'            => null,
            'controller'    => null,
            'action'        => null,
            'endPoint'      => 'Rails\Assets\Server::dispatchRequest',
            'pathRegex'     => '/^' . preg_quote($assetsPrefix, '/') . '\/(.*?)$/',
            'via' => ['get'],
            'defaults' => [
                'format'    => false,
                'path'      => null,
            ],
            'vars' => [
                'path' => [
                    'type'       => '*',
                    'constraint' => null,
                ],
            ],
            'namespaces'        => [],
            'requiredVars'      => ['path'],
            'optionalGroups'    => [],
            'anchor'            => true,
        ];
    }
}
