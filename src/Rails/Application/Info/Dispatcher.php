<?php
namespace Rails\Application\Info;

use Rails\ActionDispatch\ErrorReporting\RoutePresenter;
use Rails\ActionView\ActionView;
use Rails\ActionView\Helper\HelperSet;
use Rails\ActionView\Template\Assigns;

class Dispatcher
{
    public static function dispatchRequest($app)
    {
        $actionView = new ActionView();
        $actionView->lookupContext()->addPath(__DIR__ . '/templates');
        $actionView->lookupContext()->addPath(__DIR__ . '/../../ActionDispatch/ErrorReporting/templates');
        
        $assigns = new Assigns([
            'routes' => $app->routes()
        ]);
        
        $helperSet = new HelperSet();
        $presenter = new RoutePresenter($helperSet);
        $helperSet->invoke('setPresenter', [$presenter]);
        
        switch ($app->parameters()->action) {
            case 'routes':
                $contents = $actionView->renderTemplate(
                    'routes',
                    [
                        'partial'  => false,
                        'layout'   => 'layout',
                        'prefixes' => [],
                        'assigns'  => $assigns,
                        'helperSet' => $helperSet,
                        'details'  => [
                            'locale'   => [],
                            'handlers' => ['php'],
                            'formats'  => [],
                        ]
                    ]
                );
                
                $app->response()->setBody($contents);
                break;
            
            default:
                $app->response()->setLocation(
                    $app->routes()->pathFor('railsInfo', ['action' => 'routes'])
                );
                break;
        }
        
    }
}
