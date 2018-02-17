<?php

namespace Sweetchuck\Robo\ScssLint\Test\Helper\RoboFiles;

use Robo\Tasks;
use Sweetchuck\LintReport\Reporter\BaseReporter;
use Sweetchuck\LintReport\Reporter\CheckstyleReporter;
use Sweetchuck\LintReport\Reporter\SummaryReporter;
use Sweetchuck\LintReport\Reporter\VerboseReporter;
use League\Container\ContainerInterface;
use Sweetchuck\Robo\ScssLint\ScssLintTaskLoader;

class ScssLintRoboFile extends Tasks
{
    use ScssLintTaskLoader;

    /**
     * @var string
     */
    protected $reportsDir = 'actual';

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container)
    {
        if (!$container->has('lintCheckstyleReporter')) {
            BaseReporter::lintReportConfigureContainer($container);
        }

        return parent::setContainer($container);
    }

    /**
     * @return \Sweetchuck\Robo\ScssLint\Task\ScssLintRun|\Robo\Collection\CollectionBuilder
     */
    public function lintFilesDefaultStdOutput()
    {
        return $this
            ->taskScssLintRunFiles()
            ->setPaths(['fixtures/'])
            ->setFormat('Default');
    }

    /**
     * @return \Sweetchuck\Robo\ScssLint\Task\ScssLintRun|\Robo\Collection\CollectionBuilder
     */
    public function lintFilesDefaultFile()
    {
        return $this
            ->taskScssLintRunFiles()
            ->setPaths(['fixtures/'])
            ->setFormat('Default')
            ->setOut("{$this->reportsDir}/native.default.txt");
    }

    /**
     * @return \Sweetchuck\Robo\ScssLint\Task\ScssLintRun|\Robo\Collection\CollectionBuilder
     */
    public function lintFilesAllInOne()
    {
        $verboseFile = (new VerboseReporter())
            ->setFilePathStyle('relative')
            ->setDestination("{$this->reportsDir}/extra.verbose.txt");

        $summaryFile = (new SummaryReporter())
            ->setFilePathStyle('relative')
            ->setDestination("{$this->reportsDir}/extra.summary.txt");

        return $this
            ->taskScssLintRunFiles()
            ->setPaths(['fixtures/'])
            ->setFormat('JSON')
            ->setFailOn('warning')
            ->addLintReporter('verbose:StdOutput', 'lintVerboseReporter')
            ->addLintReporter('verbose:file', $verboseFile)
            ->addLintReporter('summary:StdOutput', 'lintSummaryReporter')
            ->addLintReporter('summary:file', $summaryFile);
    }

    /**
     * @return \Sweetchuck\Robo\ScssLint\Task\ScssLintRunInput|\Robo\Collection\CollectionBuilder
     */
    public function lintInput(
        $options = [
            'command-only' => false,
        ]
    ) {
        $fixturesDir = 'fixtures';
        $reportsDir = 'actual';

        $verboseFile = (new VerboseReporter())
            ->setFilePathStyle('relative')
            ->setDestination("$reportsDir/input.verbose.txt");

        $summaryFile = (new SummaryReporter())
            ->setFilePathStyle('relative')
            ->setDestination("$reportsDir/input.summary.txt");

        $checkstyleFile = (new CheckstyleReporter())
            ->setFilePathStyle('relative')
            ->setDestination("$reportsDir/input.checkstyle.xml");

        $files = [
            'invalid.01.scss' => [
                'fileName' => 'invalid.01.scss',
                'command' => "cat $fixturesDir/invalid.01.scss",
                'content' => null,
            ],
            'invalid.02.scss' => [
                'fileName' => 'invalid.02.scss',
                'command' => "cat $fixturesDir/invalid.02.scss",
                'content' => null,
            ],
        ];

        if (!$options['command-only']) {
            foreach ($files as $fileName => $file) {
                $files[$fileName]['content'] = file_get_contents("$fixturesDir/$fileName");
            }
        }

        return $this
            ->taskScssLintRunInput()
            ->setPaths($files)
            ->addLintReporter('verbose:StdOutput', 'lintVerboseReporter')
            ->addLintReporter('verbose:file', $verboseFile)
            ->addLintReporter('summary:StdOutput', 'lintSummaryReporter')
            ->addLintReporter('summary:file', $summaryFile)
            ->addLintReporter('checkstyle:file', $checkstyleFile);
    }
}
