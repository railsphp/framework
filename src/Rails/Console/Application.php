<?php
namespace Rails\Console;

use Symfony\Component\Console\Application as Base;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Rails\Application\Base as RailsApp;
use Rails\Console\Task\Task;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

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
        $task->setApplication($this);
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
        $output->getFormatter()->setStyle('cyan', new OutputFormatterStyle('cyan'));
        parent::doRun($input, $output);
    }
}
