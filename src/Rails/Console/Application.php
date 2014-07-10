<?php
namespace Rails\Console;

use Symfony\Component\Console\Application as Base;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Rails\Application\Base as RailsApp;
use Rails\Console\Task\Task;

class Application extends Base
{
    protected $app;
    
    public function setApp(RailsApp $app)
    {
        $this->app = $app;
    }
    
    public function addTask(Task $task)
    {
        $task = parent::add($task);
        if ($this->app) {
            $task->setApp($this->app);
        }
        return $this;
    }
    
    public function addTasks(array $tasks)
    {
        foreach ($tasks as $task) {
            $this->addTask($task);
        }
        return $this;
    }
    
    /**
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        if (true === $input->hasParameterOption(array('--version', '-V'))) {
            $output->writeln($this->getLongVersion());

            return 0;
        }

        $name = $this->getCommandName($input);
        if (true === $input->hasParameterOption(array('--help', '-h'))) {
            if (!$name) {
                $name = 'help';
                $input = new ArrayInput(array('command' => 'help'));
            }
        }

        if (!$name) {
            $name = 'list';
            $input = new ArrayInput(array('command' => 'list'));
        }

        $command = $this->find($name);

        $this->runningCommand = $command;
        $exitCode = $this->doRunCommand($command, $input, $output);
        $this->runningCommand = null;

        return $exitCode;
    }
}
