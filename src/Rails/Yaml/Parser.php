<?php
namespace Rails\Yaml;

use Symfony\Component\Yaml\Yaml as SfYaml;
use Symfony\Component\Yaml\Exception\ExceptionInterface as SfYamlException;

/**
 * Parses a YAML file. Uses LibYAML library if present,
 * otherwise uses Symfony's YAML library.
 */
class Parser
{
    protected $filepath;
    
    static public function readFile($filepath)
    {
        return (new self($filepath))->read();
    }
    
    static public function writeFile($filepath, $contents)
    {
        return (new self($filepath))->write($contents);
    }
    
    public function __construct($filepath)
    {
        $this->filepath = $filepath;
    }
    
    public function read()
    {
        if (function_exists('yaml_parse')) {
            $parsed = yaml_parse_file($this->filepath);
            if (false === $parsed) {
                throw new Exception\RuntimeException(
                    sprintf("Couldn't parse file %s", $this->filepath)
                );
            }
        } else {
            $parsed = SfYaml::parse($this->filepath);
        }
        return $parsed;
    }
    
    public function write($contents, array $params = [])
    {
        try {
            if (function_exists('yaml_emit')) {
                $params = array_merge([$contents], $params);
                $yaml = call_user_func_array('yaml_emit', $params);
                return file_put_contents($this->filepath, $yaml);
            } else {
                $yaml = call_user_func_array(
                            'Symfony\Component\Yaml\Yaml::dump',
                            array_merge([$contents], $params)
                        );
                return file_put_contents($this->filepath, $yaml);
            }
        } catch (\Exception $e) {
            $msg  = sprintf("Error while writing YAML file %s:\n", $this->filepath);
            $msg .= $e->getMessage();
            $cn   = get_class($e);
            throw new $cn($msg);
        }
    }
}
