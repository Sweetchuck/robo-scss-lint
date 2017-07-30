<?php

use Sweetchuck\AssetJar\AssetJar;
use Sweetchuck\LintReport\Reporter\BaseReporter;
use Sweetchuck\LintReport\Reporter\CheckstyleReporter;
use Sweetchuck\LintReport\Reporter\SummaryReporter;
use Sweetchuck\LintReport\Reporter\VerboseReporter;
use League\Container\ContainerInterface;

// @codingStandardsIgnoreStart
class RoboFile extends \Robo\Tasks
{
    // @codingStandardsIgnoreEnd
    use \Sweetchuck\Robo\ScssLint\ScssLintTaskLoader;

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
     * @return \Sweetchuck\Robo\ScssLint\Task\ScssLintRun
     */
    public function lintFilesDefaultStdOutput()
    {
        return $this->taskScssLintRunFiles()
            ->setPaths(['fixtures/'])
            ->setFormat('Default');
    }

    /**
     * @return \Sweetchuck\Robo\ScssLint\Task\ScssLintRun
     */
    public function lintFilesDefaultFile()
    {
        return $this->taskScssLintRunFiles()
            ->setPaths(['fixtures/'])
            ->setFormat('Default')
            ->setOut("{$this->reportsDir}/native.default.txt");
    }

    /**
     * @return \Sweetchuck\Robo\ScssLint\Task\ScssLintRun
     */
    public function lintFilesAllInOne()
    {
        $verboseFile = new VerboseReporter();
        $verboseFile
            ->setFilePathStyle('relative')
            ->setDestination("{$this->reportsDir}/extra.verbose.txt");

        $summaryFile = new SummaryReporter();
        $summaryFile
            ->setFilePathStyle('relative')
            ->setDestination("{$this->reportsDir}/extra.summary.txt");

        return $this->taskScssLintRunFiles()
            ->setPaths(['fixtures/'])
            ->setFormat('JSON')
            ->setFailOn('warning')
            ->addLintReporter('verbose:StdOutput', 'lintVerboseReporter')
            ->addLintReporter('verbose:file', $verboseFile)
            ->addLintReporter('summary:StdOutput', 'lintSummaryReporter')
            ->addLintReporter('summary:file', $summaryFile);
    }

    /**
     * @return \Sweetchuck\Robo\ScssLint\Task\ScssLintRunInput
     */
    public function lintInputWithoutJar(
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

        return $this->taskScssLintRunInput()
            ->setPaths($files)
            ->addLintReporter('verbose:StdOutput', 'lintVerboseReporter')
            ->addLintReporter('verbose:file', $verboseFile)
            ->addLintReporter('summary:StdOutput', 'lintSummaryReporter')
            ->addLintReporter('summary:file', $summaryFile)
            ->addLintReporter('checkstyle:file', $checkstyleFile);
    }

    /**
     * @return \Sweetchuck\Robo\ScssLint\Task\ScssLintRunInput
     */
    public function lintInputWithJar(
        $options = [
            'command-only' => false,
        ]
    ) {
        $task = $this->lintInputWithoutJar($options);
        $assetJar = new AssetJar([
            'l1' => [
                'l2' => $task->getPaths(),
            ],
        ]);

        return $task
            ->setPaths([])
            ->setAssetJar($assetJar)
            ->setAssetJarMap('paths', ['l1', 'l2']);
    }
}
