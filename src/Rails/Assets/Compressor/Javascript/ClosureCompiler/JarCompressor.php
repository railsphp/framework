<?php
namespace Rails\Assets\Compressor\Javascript\ClosureCompiler;

use Rails\ServiceManager\ServiceLocatorAwareTrait;

class JarCompressor extends AbstractCompressor
{
    /**
     * The "jarFile" option (full path to the compiler.jar file) must be present.
     * To pass aditional parameters or overwrite default ones, pass them in the 'params' option, like:
     *     ['compilation_level' => 'ADVANCED_OPTIMIZATIONS', ...]
     *
     * @param string $code
     * @param array $options
     * @return string
     */
    public function compress($code, array $options)
    {
        if (!function_exists('exec')) {
            throw new Exception\RuntimeException(
                "Function 'exec' is not available, can't run Jar file"
            );
        }
        
        $cmds = [
            '--compilation_level' => 'SIMPLE_OPTIMIZATIONS'
        ];
        
        if (isset($options['jarOptions'])) {
            $cmds = array_merge($cmds, $options['jarOptions']);
        }
        
        $fh       = tmpfile();
        $metaData = stream_get_meta_data($fh);
        $readFile = $metaData["uri"];
        
        file_put_contents($readFile, $code);
        
        if (isset($options['pathToJava'])) {
            $pathToJava = $options['pathToJava'];
        } else {
            $pathToJava = 'java';
        }
        
        $cmd = $pathToJava . ' -jar ' . escapeshellarg($options['jarFile']) . ' ';
        foreach ($cmds as $option => $value) {
            $cmd .= $option . ' ' . escapeshellarg($value) . ' ';
        }
        $cmd .= '--js ' . $readFile;
        
        $returnVar   = null;
        $outputLines = [];
        
        exec($cmd, $outputLines, $returnVar);
        
        fclose($fh);
        
        if ($returnVar) {
            if ($pathToJava == 'java' && $returnVar == 127) {
                $message = "exec() returned code 127, is the 'java' command available?";
            } else {
                $message = sprintf("exec() returned code %d", $returnVar);
            }
            throw new Exception\RuntimeException($message);
        }
        
        return implode($outputLines);
    }
}
