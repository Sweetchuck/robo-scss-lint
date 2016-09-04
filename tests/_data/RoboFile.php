<?php

// @codingStandardsIgnoreStart
/**
 * Class RoboFile.
 */
class RoboFile extends \Robo\Tasks
{
    // @codingStandardsIgnoreEnd
    use \Cheppers\Robo\ScssLint\Task\LoadTasks;

    /**
     * @return \Cheppers\Robo\ScssLint\Task\Run
     */
    public function lint()
    {
        return $this->taskScssLintRun()
            ->setOutput($this->getOutput())
            ->failOn('warning')
            ->format('JSON')
            ->paths(['fixtures/']);
    }
}
