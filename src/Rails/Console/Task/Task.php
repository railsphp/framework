<?php
namespace Rails\Console\Task;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Rails\Console\Exception;

abstract class Task extends Command
{
    protected $input;
    
    protected $output;
    
    /**
     * @var \Rails\Application\Base
     */
    protected $app;
    
    abstract protected function task();
    
    public function __call($method, $params)
    {
        return call_user_func_array([$this->output, $method], $params);
    }
    
    public function setApp($app)
    {
        $this->app = $app;
    }
    
    public function app()
    {
        return $this->app;
    }
    
    public function addArgument($name, $mode = null, $description = '', $default = null)
    {
        return parent::addArgument($name, $this->normalizeMode('argument', $mode), $description, $default);
    }
    
    public function addOption($name, $shortcut = null, $mode = null, $description = '', $default = null)
    {
        return parent::addOption($name, $shortcut, $this->normalizeMode('option', $mode), $description, $default);
    }
    
    public function arg($name)
    {
        return $this->input->getArgument($name);
    }
    
    public function opt($name)
    {
        return $this->input->getOption($name);
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;
        $this->task();
    }
    
    /**
     * Enables $mode to accept string as value.
     *
     * @param string|int|null $mode
     * @return int
     */
    protected function normalizeMode($type, $mode)
    {
        $normalizedMode = null;
        
        if (is_int($mode)) {
            $normalizedMode = $mode;
        } elseif (null !== $mode) {
            if (!is_array($mode)) {
                $mode = [$mode];
            }
            
            foreach ($mode as $m) {
                switch ($m) {
                    case 'optional':
                        if ($type == 'option') {
                            $value = InputOption::VALUE_OPTIONAL;
                        } else {
                            $value = InputArgument::OPTIONAL;
                        }
                        if (null === $normalizedMode) {
                            $normalizedMode = $value;
                        } else {
                            $normalizedMode = $normalizedMode | $value;
                        }
                        break;
                    
                    case 'required':
                        if ($type == 'option') {
                            $value = InputOption::VALUE_REQUIRED;
                        } else {
                            $value = InputArgument::REQUIRED;
                        }
                        if (null === $normalizedMode) {
                            $normalizedMode = $value;
                        } else {
                            $normalizedMode = $normalizedMode | $value;
                        }
                        break;
                    
                    case 'array':
                        if ($type == 'option') {
                            $value = InputOption::VALUE_IS_ARRAY;
                        } else {
                            $value = InputArgument::IS_ARRAY;
                        }
                        if (null === $normalizedMode) {
                            $normalizedMode = $value;
                        } else {
                            $normalizedMode = $normalizedMode | $value;
                        }
                        break;
                    
                    case 'none':
                        if ($type == 'option') {
                            if (null === $normalizedMode) {
                                $normalizedMode = InputOption::VALUE_NONE;
                            } else {
                                $normalizedMode = $normalizedMode | InputOption::VALUE_NONE;
                            }
                        }
                        break;
                    
                    default:
                        throw new Exception\InvalidArgumentException(
                            sprintf(
                                "Mode must be either 'none', 'optional', 'required' or 'array', passed '%s'",
                                $m
                            )
                        );
                        break;
                }
            }
        }
        
        return $normalizedMode;
    }
}
