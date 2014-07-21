<?php
namespace Rails\ActiveSupport;

/**
 * An attempt to list common web-related mime-types.
 */
class MimeTypes
{
    const OCTET_STREAM = 'application/octet-stream';
    
    protected static $mimeTypes = [
        'atom' => 'application/atom+xml',
        'json' => 'application/json',
        'js'   => 'application/javascript',
        'pdf'  => 'application/pdf',
        'xml'  => 'application/xml',
        'zip'  => 'application/zip',
        'gzip' => 'application/gzip',
        'ttf'  => 'application/x-font-ttf',
        'woff' => 'application/font-woff',
        
        'mpeg' => 'audio/mpeg',
        'webm' => [
            'audio' => 'audio/webm',
            'video' => 'video/webm',
        ],
        'mp4' => [
            'audio' => 'audio/mp4',
            'video' => 'video/mp4',
        ],
        'ogg' => [
            'audio' => 'audio/ogg',
            'video' => 'video/ogg',
        ],

        'avi'  => 'video/avi',
        'mpeg' => 'video/mpeg',
        'mov'  => 'video/quicktime',
        'mkv'  => 'video/x-matroska',
        'wmv'  => 'video/x-ms-wmv',
        'flv'  => 'video/x-flv',

        'gif'  => 'image/gif',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'ico'  => 'image/x-icon',
        'svg'  => 'image/svg+xml',
        
        'css'  => 'text/css',
        'csv'  => 'text/csv',
        'html' => 'text/html',
        'txt'  => 'text/plain',
    ];
    
    /**
     * Register an extension and its mime-type.
     * * If $extension exists and it's an array, $namespace must be passed,
     *   otherwise an exception is thrown.
     * * If $extension exists and it's not an array, the registration is
     *   silently ignored.
     */
    public static function addExtension($extension, $mimeType, $namespace = null)
    {
        if (isset(self::$mimeTypes[$extension])) {
            if (is_array(self::$mimeTypes[$extension])) {
                if (!$namespace) {
                    throw new \BadMethodCallException(sprintf(
                        "Extension '%s' already exists, must define a namespace",
                        $extension
                    ));
                }
                self::$mimeTypes[$extension][$namespace] = $mimeType;
            }
        } else {
            if ($namespace) {
                self::$mimeTypes[$extension] = [
                    $namespace => $mimeType
                ];
            } else {
                self::$mimeTypes[$extension] = $mimeType;
            }
        }
    }
    
    /**
     * Get a mime-type for an extension.
     * * If $extension exists and it's an array, $namespace must be passed,
     *   otherwise an exception is thrown. If $namespace doesn't exist,
     *   default is returned.
     * * If $extension doesn't exist, default is returned.
     *
     * Default mime-type returned is octet-stream. In order to return null
     * instead, pass false as $returnDefault.
     *
     * return string|null
     */
    public static function getMimeType($extension, $namespace = null, $returnDefault = true)
    {
        if (isset(self::$mimeTypes[$extension])) {
            if (is_array(self::$mimeTypes[$extension])) {
                if ($namespace) {
                    // throw new \BadMethodCallException(sprintf(
                        // "Must specify a namespace for extension '%s'",
                        // $extension
                    // ));
                
                    if (isset(self::$mimeTypes[$extension][$namespace])) {
                        return self::$mimeTypes[$extension][$namespace];
                    }
                }
            } else {
                return self::$mimeTypes[$extension];
            }
        }
        
        if ($returnDefault) {
            return self::OCTET_STREAM;
        } else {
            return null;
        }
    }
}
