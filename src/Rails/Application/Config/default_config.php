<?php
if (!isset($config['paths']['public_path'])) {
    $config['paths']['public_path'] = $config['paths']['root']->extend('public');
}

$config['use_cache']            = $config['environment'] == 'production';
$config['cache_store']          = [];
$config['rails_cache_store']    = [];
$config['serve_static_assets']  = false;
$config['consider_all_requests_local'] = !($config['environment'] == 'production');

$config['error'] = [
    'report_types' => E_ALL,
    'log' => true
];

$config['action_mailer'] = [
    /**
     * Allows "sendmail", "smtp" or "file".
     * Also allows a Closure that must return a transport
     * implementing Zend\Mail\Transport\TransportInterface
     */
    'delivery_method' => 'sendmail',
    
    'smtp_settings'   => [
        'host'              => '127.0.0.1',
        'name'              => 'localhost',
        'port'              => 25,
        'connection_class'  => 'login',
        'connection_config' => [
            'username'  => '',
            'password'  => '',
            'ssl'       => 'tls'
        ]
    ],
    
    'file_settings'   => [
        /**
         * Directory where mails will be stored.
         *
         * Default directory is created automatically, otherwise
         * it must already exist.
         *
         * Defaults to Rails::root() . /tmp/mail.
         */
        'path' => null,
        
        /**
         * Any callable argument.
         *
         * Defaults to Rails\ActionMailer\ActionMailer::filenameGenerator()
         */
        'name_generator' => null
    ],
    
    'default_options' => []
];
$config['action_controller'] = [
    'base' => [
        'default_charset' => 'utf-8'
    ],
    'error_handler' => 'Rails\ActionDispatch\ErrorReporting\ErrorHandler'
];
$config['assets'] = [
    'enabled' => true,

    /**
     * In development, files included by manifests are served individually to help debug javascript.
     * However, including many files can increase the page load times a lot.
     * With this option, manifest files and their children are concatenated, resulting in 1 file.
     */
    'concat' => false,

    /**
     * Additional absolute paths to search assets in.
     */
    'paths' => [],

    /**
     * Defines how an extension is handled for css files.
     *
     * <pre>
     * 'compilers' => [
     *     'scss' => [
     *         'class'  => 'ScssCompiler',
     *         'method' => 'compile',
     *         'static' => true
     *     ]
     * ];
     * </pre>
     *
     * The array values are actually parameters to create instances of
     * Rails\Config\MethodConfig.
     *
     * @see Rails\Config\MethodConfig
     */
    'compilers' => [
        'css'    => 'Rails\Assets\Compiler\CssCompiler',
        'scss'   => 'Rails\Assets\Compiler\ScssCompiler',
        'js'     => 'Rails\Assets\Compiler\JsCompiler',
        'coffee' => 'Rails\Assets\Compiler\CoffeeCompiler'
    ],

    /**
     * This is Not zip compression.
     * This tells Assets to minify files upon compile using the compressors
     * defined below.
     */
    'compress' => false,

    'compressors' => [
        /**
         * Css compressor.
         * Value will be passed to MethodConfig constructor.
         *
         * @see Rails\Config\MethodConfig
         */
        'css' => [
            'class'  => 'Rails\Assets\Compressor\Css\CssMin',
            'method' => 'compress',
            'static' => true
        ],
        
        /**
         * Javascript compressor.
         * Accepts same value as css compressor
         */
        'js' => [
            'class'  => 'Rails\Assets\Compressor\Javascript\ClosureCompiler\ClosureCompiler',
            'method' => 'compressCode',
            'static' => true
        ],
    ],

    /**
     * Create a gzipped version of compiled files.
     * Uses gzencode()
     */
    'gz_compression' => true,

    'gz_compression_level' => 9,

    /**
     * Names of the manifest files that will be compiled upon
     * Assets::compileAll(). Only names, no paths.
     */
    'precompile' => [
        'application.js',
        'application.css'
    ],

    /**
     * Non js or css files that will be copied to the public assets folder
     * when compiling. These values will be passed to glob() with GLOB_BRACE.
     */
    'patterns' => [
        '*.gif',
        '*.png',
        '*.jpg',
        '*.jpeg',
    ],

    /**
     * This is used as the URL path to server non-static assets for
     * development, and is also the name of the folder where assets are
     * stored.
     */
    'prefix' => '/assets',

    # Generate digests for assets URLs
    'digest' => false,

    'host' => ''
];
