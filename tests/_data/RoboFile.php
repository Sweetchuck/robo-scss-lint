<?php

use Cheppers\LintReport\Reporter\BaseReporter;
use Cheppers\LintReport\Reporter\SummaryReporter;
use Cheppers\LintReport\Reporter\VerboseReporter;
use League\Container\ContainerInterface;

// @codingStandardsIgnoreStart
class RoboFile extends \Robo\Tasks
{
    // @codingStandardsIgnoreEnd
    use \Cheppers\Robo\ScssLint\ScssLintTaskLoader;

    /**
     * @var string
     */
    protected $reportsDir = 'actual';

    /**
     * @param \League\Container\ContainerInterface $container
     *
     * @return $this
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;

        BaseReporter::lintReportConfigureContainer($this->container);

        return $this;
    }

    /**
     * @return \Cheppers\Robo\ScssLint\Task\Run
     */
    public function lintDefaultStdOutput()
    {
        return $this->taskScssLintRun()
            ->setPaths(['fixtures/'])
            ->setFormat('Default');
    }

    /**
     * @return \Cheppers\Robo\ScssLint\Task\Run
     */
    public function lintDefaultFile()
    {
        return $this->taskScssLintRun()
            ->setPaths(['fixtures/'])
            ->setFormat('Default')
            ->setOut("{$this->reportsDir}/native.default.txt");
    }

    /**
     * @return \Cheppers\Robo\ScssLint\Task\Run
     */
    public function lintAllInOne()
    {
        $verboseFile = new VerboseReporter();
        $verboseFile
            ->setFilePathStyle('relative')
            ->setDestination("{$this->reportsDir}/extra.verbose.txt");

        $summaryFile = new SummaryReporter();
        $summaryFile
            ->setFilePathStyle('relative')
            ->setDestination("{$this->reportsDir}/extra.summary.txt");

        return $this->taskScssLintRun()
            ->setPaths(['fixtures/'])
            ->setFormat('JSON')
            ->setFailOn('warning')
            ->addLintReporter('verbose:StdOutput', 'lintVerboseReporter')
            ->addLintReporter('verbose:file', $verboseFile)
            ->addLintReporter('summary:StdOutput', 'lintSummaryReporter')
            ->addLintReporter('summary:file', $summaryFile);
    }
}
