<?php

namespace Cheppers\Robo\ScssLint\Task;

use League\Container\ContainerAwareInterface;
use Robo\Contract\OutputAwareInterface;

/**
 * Class LoadTasks.
 *
 * @package Cheppers\Robo\ScssLint\Task
 */
trait LoadTasks
{

    /**
     * Wrapper for scss-lint.
     *
     * @param array $options
     *   Key-value pairs of options.
     * @param string[] $paths
     *   File paths.
     *
     * @return \Cheppers\Robo\ScssLint\Task\Run A lint runner task instance.
     *   A lint runner task instance.
     */
    protected function taskScssLintRun(array $options = [], array $paths = [])
    {
        /** @var \Cheppers\Robo\ScssLint\Task\Run $task */
        $task = $this->task(Run::class, $options, $paths);
        if ($this instanceof ContainerAwareInterface) {
            $task->setContainer($this->getContainer());
        }

        if ($this instanceof OutputAwareInterface) {
            $task->setOutput($this->output());
        }

        return $task;
    }
}
