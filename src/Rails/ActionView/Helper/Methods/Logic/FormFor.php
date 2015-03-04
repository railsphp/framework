<?php
namespace Rails\ActionView\Helper\Methods\Logic;

use Closure;
use Rails\Routing\ActionToken;

class FormFor
{
    public function render($baseHelper, $model, $options, Closure $block = null)
    {
        if ($options instanceof Closure) {
            $block   = $options;
            $options = [];
        }
        
        $inf = $baseHelper->getService('inflector');
        $modelClass = get_class($model);
        $controller = str_replace('/', ActionToken::NAMESPACE_SEPARATOR,
                        lcfirst(
                            $inf->pluralize($modelClass)
                        ));
        
        // $primaryKey = $modelClass::primaryKey();
        $urlPath    = null;
        
        if (isset($options['url'])) {
            if ($baseHelper->isUrl($options['url'])) {
                $urlPath = $options['url'];
            } else {
                if (is_array($options['url'])) {
                    $token = new ActionToken($options['url'][0]);
                } else {
                    # Action token (string) assumed.
                    $token = new ActionToken($options['url']);
                }
                $action  = $token->action();
                $urlPath = $baseHelper->urlFor($token->toString());
            }
        } else {
            if ($id = $model->id()) {
                $action = 'update';
                $urlParams = [$controller . '#' . $action, $model];
            } else {
                $action = 'create';
                $urlParams = [$controller . '#' . $action];
            }
            $urlPath = $baseHelper->urlFor($urlParams);
        }
        
        if (!isset($options['html'])) {
            $htmlAttrs = [];
        } else {
            $htmlAttrs = $options['html'];
        }
        
        if (isset($options['as'])) {
            $inputNamespace = $options['as'];
        } else {
            $inputNamespace = null;
        }
        
        switch ($action) {
            case 'create':
                $resourceName = $baseHelper->objectName($model);
                if (empty($htmlAttrs['method'])) {
                    $htmlAttrs['method'] = 'post';
                }
                if (empty($htmlAttrs['id'])) {
                    $htmlAttrs['id'] = 'new_' . $resourceName;
                }
                $baseHelper->addClass($htmlAttrs, 'new_' . $resourceName);
                break;
            
            case 'update':
                $resourceName = $baseHelper->objectName($model);
                if (empty($htmlAttrs['method'])) {
                    $htmlAttrs['method'] = 'patch';
                }
                if (empty($htmlAttrs['id'])) {
                    $htmlAttrs['id'] = 'edit_' . $resourceName . '_' . $model->id();
                }
                $baseHelper->addClass($htmlAttrs, 'edit_' . $resourceName);
                break;
            
            case 'destroy':
                if (empty($htmlAttrs['method'])) {
                    $htmlAttrs['method'] = 'delete';
                }
                break;
        }
        
        if (!empty($options['remote'])) {
            $htmlAttrs['data-remote'] = 'true';
        }
        
        if (empty($options['builder'])) {
            $formBuilder = $baseHelper->helperSet()->invoke('getFormBuilder', [$model, $inputNamespace]);
        } else {
            # "builder" option could be the name of a class extending FormBuilder.
            $formBuilder = new $options['builder']($baseHelper->helperSet(), $model, $inputNamespace);
        }
        
        return $baseHelper->helperSet()->invoke(
            'formTag',
            [
                $urlPath,
                $htmlAttrs,
                function() use ($block, $formBuilder) {
                    $formBuilder->runBlock($block);
                }
            ]
        );
    }
}
