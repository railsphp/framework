<?php
namespace Rails\Assets\Compressor\Javascript\ClosureCompiler;

use Rails\ServiceManager\ServiceLocatorAwareTrait;

class ApiCompressor extends AbstractCompressor
{
    const API_URL = 'http://closure-compiler.appspot.com/compile';
    
    /**
     * Options for the Java command can be passed in the 'jarOptions' option, like:
     *     [ '--compilation_level' => 'ADVANCED_OPTIMIZATIONS', ... ]
     * Values will be escaped with `escapeshellarg()`.
     * The full path to the Java executable can be passed in the 'pathToJava' option,
     * otherwise the 'java' command will be called.
     *
     * @param string $code
     * @param array $options
     * @return string
     */
    public function compress($code, array $options)
    {
        $params = [
            'compilation_level' => 'SIMPLE_OPTIMIZATIONS',
            'output_info'       => 'compiled_code',
            'output_format'     => 'text',
            'language'          => 'ECMASCRIPT5'
        ];
        
        if (isset($options['params'])) {
            $params  = array_merge($params, $options['params']);
        }
        
        $params['js_code'] = $code;
        
        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST            => true,
            CURLOPT_POSTFIELDS      => http_build_query($params),
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_HTTPHEADER      => [
                "Content-type: application/x-www-form-urlencoded"
            ]
        ]);
        
        $resp     = curl_exec($ch);
        $curlInfo = curl_getinfo($ch);
        curl_close($ch);
        
        if (!trim($resp)) {
            $emptyStrMsg = "Closure service returned an empty string. cURL info:\n";
            
            ob_start();
            var_dump($curlInfo);
            $info = ob_get_clean();
            
            $this->getService('logger')->message(
                $emptyStrMsg . $info
            );
            
            throw new Exception\BlankResponseException(
                sprintf(
                    "%s (file length: %d). cURL info was logged.",
                    $emptyStrMsg,
                    strlen($code)
                )
            );
        }
        
        return $resp;
    }
}
