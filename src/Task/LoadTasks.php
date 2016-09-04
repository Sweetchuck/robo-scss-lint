<?php

namespace Cheppers\Robo\ScssLint\Task;

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
    public function taskScssLintRun(array $options = [], array $paths = [])
    {
        return $this->task(Run::class, $options, $paths);
    }
}
