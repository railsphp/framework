<?php
namespace Rails\Console\Task;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArgvInput;
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
    
    /**
     * Creates a file with console otput.
     *
     * @param string $filePath  Full path to file
     * @param string $contents  The contents of the file
     */
    protected function createFile($filePath, $contents)
    {
        $this->createDirectory(dirname($filePath));
        
        if (is_file($filePath)) {
            // if (!$this->opt('force')) {
            $this->output->setDecorated(true);
            $this->output->writeln(sprintf(
                "<comment>file exists  <cyan>%s</cyan></comment>",
                $this->prettyPath($filePath)
            ));
                // return;
            // } else {
                // $this->output->writeln(sprintf(
                    // "<comment>overwriten  <cyan>%s</cyan></comment>",
                    // $filePath
                // ));
            // }
        } else {
            $this->output->writeln(sprintf(
                "<info>create  <cyan>%s</cyan></info>",
                $this->prettyPath($filePath)
            ));
            file_put_contents($filePath, $contents);
        }
        
    }
    
    /**
     * Creates a directory with console otput.
     *
     * @param string $dirPath   Full directory path
     */
    protected function createDirectory($dirPath)
    {
        // $fullPath = $this->app->config()['paths']['root']->expand($dirPath);
        
        if (!is_dir($dirPath)) {
            $this->output->writeln(sprintf(
                "<info>create  <cyan>%s</cyan></info>",
                $this->prettyPath($dirPath)
            ));
            mkdir($dirPath, 0775, true);
        }
    }
    
    protected function invokeTask($generatorName, array $argvParams)
    {
        array_unshift($argvParams, '', '');
        $input  = new ArgvInput($argvParams);
        $output = $this->output;
        
        $task = $this->getApplication()->get($generatorName);
        $task->run($input, $output);
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
    
    protected function prettyPath($path)
    {
        return substr($path, strlen($this->app->config()['paths']['root']) + 1);
    }
}
