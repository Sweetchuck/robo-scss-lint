<?php

/**
 * Class RoboFile.
 */
class RoboFile extends \Robo\Tasks
    // @codingStandardsIgnoreEnd
{
    use \Cheppers\Robo\Task\ScssLint\LoadTasks;

    /**
     * RoboFile constructor.
     */
    public function __construct()
    {
        $this->setContainer(\Robo\Robo::getContainer());

        /** @var \League\Container\Container $c */
        $c = $this->getContainer();
        $c
            ->addServiceProvider(static::getScssLintServiceProvider())
            ->addServiceProvider(\Robo\Task\Filesystem\loadTasks::getFilesystemServices());
    }

    /**
     * @return \Cheppers\Robo\Task\ScssLint\TaskScssLintRun
     */
    public function lint()
    {
        return $this->taskScssLintRun()
            ->format('JSON')
            ->paths(['fixtures/']);
    }

}
