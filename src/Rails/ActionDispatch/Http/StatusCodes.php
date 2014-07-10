<?php
namespace Rails\ActionDispatch\Http;

abstract class StatusCodes
{
    protected static $HTTP_STATUS_CODES = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Reserved',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        426 => 'Upgrade Required',
        427 => 'Unassigned',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        430 => 'Unassigned',
        431 => 'Request Header Fields Too Large',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        509 => 'Unassigned',
        510 => 'Not Extended',
        511 => 'Network Authentication Required'
    ];
    
    protected static $NAMES_TO_CODES = [
        'continue'                      => 100,
        'switchingProtocols'            => 101,
        'processing'                    => 102,
        'ok'                            => 200,
        'created'                       => 201,
        'accepted'                      => 202,
        'nonAuthoritativeInformation'   => 203,
        'noContent'                     => 204,
        'resetContent'                  => 205,
        'partialContent'                => 206,
        'multiStatus'                   => 207,
        'alreadyReported'               => 208,
        'imUsed'                        => 226,
        'multipleChoices'               => 300,
        'movedPermanently'              => 301,
        'found'                         => 302,
        'seeOther'                      => 303,
        'notModified'                   => 304,
        'useProxy'                      => 305,
        'reserved'                      => 306,
        'temporaryRedirect'             => 307,
        'permanentRedirect'             => 308,
        'badRequest'                    => 400,
        'unauthorized'                  => 401,
        'paymentRequired'               => 402,
        'forbidden'                     => 403,
        'notFound'                      => 404,
        'methodNotAllowed'              => 405,
        'notAcceptable'                 => 406,
        'proxyAuthenticationRequired'   => 407,
        'requestTimeout'                => 408,
        'conflict'                      => 409,
        'gone'                          => 410,
        'lengthRequired'                => 411,
        'preconditionFailed'            => 412,
        'requestEntityTooLarge'         => 413,
        'request_uriTooLong'            => 414,
        'unsupportedMediaType'          => 415,
        'requestedRangeNotSatisfiable'  => 416,
        'expectationFailed'             => 417,
        'unprocessableEntity'           => 422,
        'locked'                        => 423,
        'failedDependency'              => 424,
        'upgradeRequired'               => 426,
        'unassigned'                    => 427,
        'preconditionRequired'          => 428,
        'tooManyRequests'               => 429,
        'unassigned'                    => 430,
        'requestHeaderFieldsTooLarge'   => 431,
        'internalServerError'           => 500,
        'notImplemented'                => 501,
        'badGateway'                    => 502,
        'serviceUnavailable'            => 503,
        'gatewayTimeout'                => 504,
        'httpVersionNotSupported'       => 505,
        'insufficientStorage'           => 507,
        'loopDetected'                  => 508,
        'unassigned'                    => 509,
        'notExtended'                   => 510,
        'networkAuthenticationRequired' => 511
    ];
    
    /**
     * addStatus(900, "The Nine-Hundred Status");
     */
    public static function addStatus($code, $name)
    {
        self::$HTTP_STATUS_CODES[$code] = $name;
        self::addCodeName($name);
    }
    
    /**
     * @param string|int $camelizedName
     */
    public static function getCode($status)
    {
        if (is_string($status)) {
            if (isset(self::$NAMES_TO_CODES[$status])) {
                return self::$NAMES_TO_CODES[$status];
            } else {
                return 500;
            }
        } else {
            return (int)$status;
        }
    }
    
    /**
     * @param int $statusCode
     */
    public static function getName($statusCode)
    {
        if (isset(self::$HTTP_STATUS_CODES[$statusCode])) {
            return self::$HTTP_STATUS_CODES[$statusCode];
        } else {
            return self::$HTTP_STATUS_CODES[500];
        }
    }
    
    protected static function addCodeName($name, $status)
    {
        self::$NAMES_TO_CODES[
            str_replace(' ', '', ucwords(preg_replace('/\W/', ' ', $name)))
        ] = $status;
    }
}
