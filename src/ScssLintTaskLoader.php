<?php

namespace Sweetchuck\Robo\ScssLint;

use League\Container\ContainerAwareInterface;
use Robo\Contract\OutputAwareInterface;

trait ScssLintTaskLoader
{
    /**
     * Wrapper for scss-lint.
     *
     * @param array $options
     *   Key-value pairs of options.
     *
     * @return \Sweetchuck\Robo\ScssLint\Task\ScssLintRunFiles|\Robo\Collection\CollectionBuilder
     *   A lint runner task instance.
     */
    protected function taskScssLintRunFiles(array $options = [])
    {
        /** @var \Sweetchuck\Robo\ScssLint\Task\ScssLintRunFiles $task */
        $task = $this->task(Task\ScssLintRunFiles::class, $options);
        if ($this instanceof ContainerAwareInterface) {
            $task->setContainer($this->getContainer());
        }

        if ($this instanceof OutputAwareInterface) {
            $task->setOutput($this->output());
        }

        return $task;
    }

    /**
     * Wrapper for scss-lint.
     *
     * @param array $options
     *   Key-value pairs of options.
     *
     * @return \Sweetchuck\Robo\ScssLint\Task\ScssLintRunInput|\Robo\Collection\CollectionBuilder
     *   A lint runner task instance.
     */
    protected function taskScssLintRunInput(array $options = [])
    {
        /** @var \Sweetchuck\Robo\ScssLint\Task\ScssLintRunInput $task */
        $task = $this->task(Task\ScssLintRunInput::class, $options);
        if ($this instanceof ContainerAwareInterface) {
            $task->setContainer($this->getContainer());
        }

        if ($this instanceof OutputAwareInterface) {
            $task->setOutput($this->output());
        }

        return $task;
    }
}
