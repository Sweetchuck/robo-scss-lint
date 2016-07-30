<?php

namespace Cheppers\Robo\Task\ScssLint;

use Robo\Container\SimpleServiceProvider;

/**
 * Class LoadTasks.
 *
 * @package Cheppers\Robo\Task\ScssLint
 */
trait LoadTasks
{

    /**
     * @return \League\Container\ServiceProvider\SignatureServiceProviderInterface
     */
    public static function getScssLintServiceProvider()
    {
        return new SimpleServiceProvider([
            'taskScssLintRun' => TaskScssLintRun::class,
        ]);
    }

    /**
     * Wrapper for scss-lint.
     *
     * @param array $options
     *   Key-value pairs of options.
     * @param string[] $paths
     *   File paths.
     *
     * @return \Cheppers\Robo\Task\ScssLint\TaskScssLintRun A lint runner task instance.
     *   A lint runner task instance.
     */
    public function taskScssLintRun(array $options = [], array $paths = [])
    {
        return $this->task(__FUNCTION__, $options, $paths);
    }
}
