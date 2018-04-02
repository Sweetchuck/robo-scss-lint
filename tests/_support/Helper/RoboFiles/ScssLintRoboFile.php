<?php

namespace Sweetchuck\Robo\ScssLint\Test\Helper\RoboFiles;

use Robo\Tasks;
use Sweetchuck\LintReport\Reporter\BaseReporter;
use Sweetchuck\LintReport\Reporter\CheckstyleReporter;
use Sweetchuck\LintReport\Reporter\SummaryReporter;
use Sweetchuck\LintReport\Reporter\VerboseReporter;
use League\Container\ContainerInterface;
use Sweetchuck\Robo\ScssLint\ScssLintTaskLoader;
use Webmozart\PathUtil\Path;

class ScssLintRoboFile extends Tasks
{
    use ScssLintTaskLoader;

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
        $dataDir = $this->getDataDir();

        return $this
            ->taskScssLintRunFiles()
            ->setConfigFile("$dataDir/.scss-lint.yml")
            ->setFormat('Default')
            ->setPaths(["$dataDir/fixtures/"])
            ;
    }

    /**
     * @return \Sweetchuck\Robo\ScssLint\Task\ScssLintRun|\Robo\Collection\CollectionBuilder
     */
    public function lintFilesDefaultFile()
    {
        $dataDir = $this->getDataDir();

        return $this
            ->taskScssLintRunFiles()
            ->setConfigFile("$dataDir/.scss-lint.yml")
            ->setFormat('Default')
            ->setOut("$dataDir/actual/native.default.txt")
            ->setPaths(["$dataDir/fixtures"]);
    }

    /**
     * @return \Sweetchuck\Robo\ScssLint\Task\ScssLintRun|\Robo\Collection\CollectionBuilder
     */
    public function lintFilesAllInOne()
    {
        $dataDir = $this->getDataDir();

        $verboseFile = (new VerboseReporter())
            ->setFilePathStyle('relative')
            ->setDestination("$dataDir/actual/extra.verbose.txt");

        $summaryFile = (new SummaryReporter())
            ->setFilePathStyle('relative')
            ->setDestination("$dataDir/actual/extra.summary.txt");

        return $this
            ->taskScssLintRunFiles()
            ->setPaths(["$dataDir/fixtures/"])
            ->setFormat('JSON')
            ->setConfigFile("$dataDir/.scss-lint.yml")
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
        $dataDir = $this->getDataDir();

        $verboseFile = (new VerboseReporter())
            ->setFilePathStyle('relative')
            ->setDestination("$dataDir/actual/input.verbose.txt");

        $summaryFile = (new SummaryReporter())
            ->setFilePathStyle('relative')
            ->setDestination("$dataDir/actual/input.summary.txt");

        $checkstyleFile = (new CheckstyleReporter())
            ->setFilePathStyle('relative')
            ->setDestination("$dataDir/actual/input.checkstyle.xml");

        $files = [
            'invalid.01.scss' => [
                'fileName' => 'invalid.01.scss',
                'command' => sprintf('cat %s', escapeshellarg("$dataDir/fixtures/invalid.01.scss")),
                'content' => null,
            ],
            'invalid.02.scss' => [
                'fileName' => 'invalid.02.scss',
                'command' => sprintf('cat %s', escapeshellarg("$dataDir/fixtures/invalid.02.scss")),
                'content' => null,
            ],
        ];

        if (!$options['command-only']) {
            foreach ($files as $fileName => $file) {
                $files[$fileName]['content'] = file_get_contents("$dataDir/fixtures/$fileName");
            }
        }

        return $this
            ->taskScssLintRunInput()
            ->setConfigFile("$dataDir/.scss-lint.yml")
            ->setPaths($files)
            ->addLintReporter('verbose:StdOutput', 'lintVerboseReporter')
            ->addLintReporter('verbose:file', $verboseFile)
            ->addLintReporter('summary:StdOutput', 'lintSummaryReporter')
            ->addLintReporter('summary:file', $summaryFile)
            ->addLintReporter('checkstyle:file', $checkstyleFile);
    }

    protected function getProjectRootDir(): string
    {
        return Path::canonicalize(__DIR__ . '/../../../..');
    }

    protected function getDataDir(): string
    {
        $root = $this->getProjectRootDir();

        return Path::makeRelative("$root/tests/_data", getcwd());
    }
}
