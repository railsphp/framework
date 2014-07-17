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
                'alias' => 'railsInfo',
                'via' => ['get'],
                'anchor' => true,
                'as' => null,
            ],
            'scope' => [],
            'segmentKeys' => ['action'],
            'isBuilt' => true,
            'path' => '/rails/info(/:action)',
            'name' => 'railsInfo',
            'to' => null,
            'controller' => null,
            'action' => null,
            'endPoint' => 'Rails\Application\Info\Dispatcher::dispatchRequest',
            'pathRegex' => '/^\/rails\/info(?:\/(properties|routes))?$/',
            'via' => ['get'],
            'defaults' => [
                'format' => false,
                'alias' => 'railsInfo',
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
                // 'format'    => false,
                'via'       => ['get'],
                'anchor'    => true,
                // 'as'        => null,
                'alias'     => null,
            ],
            'scope'         => [],
            'segmentKeys'   => null,
            'isBuilt'       => true,
            'path'          => $assetsPrefix . '/*path(.:format)',
            'name'          => null,
            'to'            => null,
            'controller'    => null,
            'action'        => null,
            'endPoint'      => 'Rails\Assets\Server::dispatchRequest',
            'pathRegex'     => '/^' . preg_quote($assetsPrefix, '/') . '\/(.*?)(?:\.([^\/\.]+?))?$/',
            'via' => ['get'],
            'defaults' => [
                'format'    => null,
                'path'      => null,
            ],
            'vars' => [
                'path' => [
                    'type'       => '*',
                    'constraint' => null,
                ],
                'format' => [
                    'type'       => ':',
                    'constraint' => null
                ]
            ],
            'namespaces'        => [],
            'requiredVars'      => ['path'],
            'optionalGroups'    => [
                [
                    'part' => '(.:format)',
                    ['format']
                ]
            ],
            'anchor'            => true
        ];
    }
}
