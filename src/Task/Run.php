<?php

namespace Cheppers\Robo\ScssLint\Task;

use Cheppers\AssetJar\AssetJarAware;
use Cheppers\AssetJar\AssetJarAwareInterface;
use Cheppers\LintReport\ReporterInterface;
use Cheppers\Robo\ScssLint\LintReportWrapper\ReportWrapper;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Common\BuilderAwareTrait;
use Robo\Common\IO;
use Robo\Contract\BuilderAwareInterface;
use Robo\Contract\CommandInterface;
use Robo\Contract\OutputAwareInterface;
use Robo\Result;
use Robo\Task\BaseTask;
use Symfony\Component\Process\Process;

class Run extends BaseTask implements
    AssetJarAwareInterface,
    CommandInterface,
    ContainerAwareInterface,
    BuilderAwareInterface,
    OutputAwareInterface
{
    use AssetJarAware;
    use ContainerAwareTrait;
    use BuilderAwareTrait;
    use IO;

    /**
     * Exit code: No lints were found.
     */
    const EXIT_CODE_OK = 0;

    /**
     * Lints with a severity of warning were reported (no errors).
     */
    const EXIT_CODE_WARNING = 1;

    /**
     * One or more errors were reported (and any number of warnings).
     */
    const EXIT_CODE_ERROR = 2;

    const EXIT_CODE_INVALID = 3;

    /**
     * No SCSS files matched by the patterns.
     */
    const EXIT_CODE_NO_FILES = 80;

    /**
     * @todo Some kind of dependency injection would be awesome.
     *
     * @var string
     */
    protected $processClass = Process::class;

    //region Options.
    //region Option - workingDirectory.
    /**
     * Directory to step in before run the `scss-lint`.
     *
     * @var string
     */
    protected $workingDirectory = '';

    public function getWorkingDirectory(): string
    {
        return $this->workingDirectory;
    }

    /**
     * Set the current working directory.
     *
     * @return $this
     */
    public function setWorkingDirectory(string $value)
    {
        $this->workingDirectory = $value;

        return $this;
    }
    //endregion

    //region Option - bundleGemFile.
    /**
     * @var string
     */
    protected $bundleGemFile = '';

    public function getBundleGemFile(): string
    {
        return $this->bundleGemFile;
    }

    /**
     * @return $this
     */
    public function setBundleGemFile(string $bundleGemFile)
    {
        $this->bundleGemFile = $bundleGemFile;

        return $this;
    }
    //endregion

    //region Option - bundleExecutable.
    /**
     * @var string
     */
    protected $bundleExecutable = 'bundle';

    protected function getBundleExecutable(): string
    {
        return $this->bundleExecutable;
    }

    /**
     * @return $this
     */
    protected function setBundleExecutable(string $value)
    {
        $this->bundleExecutable = $value;

        return $this;
    }
    //endregion

    //region Option - scssLintExecutable.
    /**
     * @var string
     */
    protected $scssLintExecutable = 'scss-lint';

    public function getScssLintExecutable(): string
    {
        return $this->scssLintExecutable;
    }

    /**
     * @return $this
     */
    public function setScssLintExecutable(string $scssLintExecutable)
    {
        $this->scssLintExecutable = $scssLintExecutable;

        return $this;
    }
    //endregion

    //region Option - failOn.
    /**
     * Severity level.
     *
     * @var string
     */
    protected $failOn = 'error';

    public function getFailOn(): string
    {
        return $this->failOn;
    }

    /**
     * Fail if there is a lint with warning severity.
     *
     * @param string $value
     *   Allowed values are: never, warning, error.
     *
     * @return $this
     */
    public function setFailOn(string $value)
    {
        $this->failOn = $value;

        return $this;
    }
    //endregion

    //region Option - failOnNoFiles.
    /**
     * Fail if there is no SCSS file to lint.
     *
     * @var bool
     */
    protected $failOnNoFiles = false;

    public function getFailOnNoFiles(): bool
    {
        return $this->failOnNoFiles;
    }

    /**
     * Fail if there is no SCSS file to lint.
     *
     * @return $this
     */
    public function setFailOnNoFiles(bool $value)
    {
        $this->failOnNoFiles = $value;

        return $this;
    }
    //endregion

    //region Option - lintReporters.
    /**
     * @var \Cheppers\LintReport\ReporterInterface[]
     */
    protected $lintReporters = [];

    /**
     * @return \Cheppers\LintReport\ReporterInterface[]
     */
    public function getLintReporters(): array
    {
        return $this->lintReporters;
    }

    /**
     * @param array $lintReporters
     *
     * @return $this
     */
    public function setLintReporters(array $lintReporters)
    {
        $this->lintReporters = $lintReporters;

        return $this;
    }

    /**
     * @param string $id
     * @param string|\Cheppers\LintReport\ReporterInterface $lintReporter
     *
     * @return $this
     */
    public function addLintReporter(string $id, $lintReporter = null)
    {
        $this->lintReporters[$id] = $lintReporter;

        return $this;
    }

    /**
     * @return $this
     */
    public function removeLintReporter(string $id)
    {
        unset($this->lintReporters[$id]);

        return $this;
    }
    //endregion

    //region Option - format.
    /**
     * Specify how to display lints.
     *
     * @var string
     */
    protected $format = '';

    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * Specify how to display lints.
     *
     * @param string $value
     *   Formatter identifier. By default the following formatters are supported:
     *   - CleanFiles
     *   - Config
     *   - Default
     *   - Files
     *   - JSON
     *   - Stats
     *   - TAP
     *
     * @return $this
     */
    public function setFormat(string $value)
    {
        $this->format = $value;

        return $this;
    }
    //endregion

    //region Option - requires.
    /**
     * Required Ruby files.
     *
     * @var array
     */
    protected $require = [];

    public function getRequire(): array
    {
        return $this->require;
    }

    /**
     * Add or remove gems.
     *
     * @param string|string[]|bool[] $gems
     *   Gem names.
     * @param bool $include
     *   Add or remove.
     *
     * @return $this
     */
    public function setRequire($gems, bool $include = true)
    {
        if (!is_array($gems)) {
            $gems = [$gems => $include];
        }

        $this->require = $this->createIncludeList($gems, $include) + $this->require;

        return $this;
    }
    //endregion

    //region Option - linters.
    /**
     * Linters to include or exclude.
     *
     * @var array
     */
    protected $linters = [];

    public function getLinters(): array
    {
        return $this->linters;
    }

    /**
     * Add or remove gems.
     *
     * @param string|string[]|bool[] $names
     *   Gem names.
     * @param bool|null $include
     *   Add or remove or neutral.
     *
     * @return $this
     */
    public function setLinters($names, bool $include = true)
    {
        if (!is_array($names)) {
            $names = [$names => $include];
        }

        $this->linters = $this->createIncludeList($names, $include) + $this->linters;

        return $this;
    }
    //endregion

    //region Option - configFile.
    /**
     * Config file path.
     *
     * @var string
     */
    protected $configFile = '';

    public function getConfigFile(): string
    {
        return $this->configFile;
    }

    /**
     * Specify which configuration file you want to use.
     *
     * @return $this
     */
    public function setConfigFile(string $path)
    {
        $this->configFile = $path;

        return $this;
    }
    //endregion

    //region Option - exclude.
    /**
     * SCSS files to exclude.
     *
     * @var array
     */
    protected $exclude = [];

    public function getExclude(): array
    {
        return $this->exclude;
    }

    /**
     * List of file names to exclude.
     *
     * @param string|string[]|bool[] $filePaths
     *   File names.
     * @param bool $include
     *   If TRUE $filePaths will be added to the exclude list.
     *
     * @return $this
     */
    public function setExclude($filePaths, bool $include = true)
    {
        if (!is_array($filePaths)) {
            $filePaths = [$filePaths => $include];
        }

        $this->exclude = $this->createIncludeList($filePaths, $include) + $this->exclude;

        return $this;
    }
    //endregion

    //region Option - out.
    /**
     * Write output to a file instead of STDOUT.
     *
     * @var string
     */
    protected $out = '';

    public function getOut(): string
    {
        return $this->out;
    }

    /**
     * Write output to a file instead of STDOUT.
     *
     * @param string|null $filePath
     *
     * @return $this
     */
    public function setOut(string $filePath)
    {
        $this->out = $filePath;

        return $this;
    }
    //endregion

    //region Option - colorize.
    /**
     * Force output to be colorized.
     *
     * @var bool|null
     */
    protected $colorize = null;

    public function getColor(): ?bool
    {
        return $this->colorize;
    }

    /**
     * Force output to be colorized.
     *
     * @return $this
     */
    public function setColor(?bool $colorize)
    {
        $this->colorize = $colorize;

        return $this;
    }
    //endregion

    //region Option - paths.
    /**
     * SCSS files to check.
     *
     * @var array
     */
    protected $paths = [];

    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * File paths to lint.
     *
     * @param string|string[]|bool[] $paths
     *   Key-value pair of file names and boolean.
     * @param bool $include
     *   Exclude or include the files in $paths.
     *
     * @return $this
     */
    public function setPaths(array $paths, bool $include = true)
    {
        $this->paths = $this->createIncludeList($paths, $include) + $this->paths;

        return $this;
    }
    //endregion
    //endregion

    protected $options = [
        'format' => 'value',
        'require' => 'multi-value',
        'linter' => 'include-exclude',
        'exclude' => 'list',
        'config' => 'value',
        'out' =>  'value',
        'color' => 'tri-state',
    ];

    /**
     * Process exit code.
     *
     * @var int
     */
    protected $exitCode = 0;

    /**
     * Exit code and error message mapping.
     *
     * @var string
     */
    protected $exitMessages = [
        0 => 'No lints were found',
        1 => 'Lints with a severity of warning were reported (no errors)',
        2 => 'One or more errors were reported (and any number of warnings)',
        3 => 'Extra lint reporters can be used only if the output format is "json".',
        64 => 'Command line usage error (invalid flag, etc.)',
        66 => 'One or more files specified were not found',
        69 => 'Required library specified via -r/--require flag was not found',
        70 => 'Unexpected error (i.e. a bug); please report it',
        78 => 'Invalid configuration file; your YAML is likely incorrect',
        80 => 'Files glob patterns specified did not match any files.',
    ];

    /**
     * TaskScssLintRun constructor.
     *
     * @param array $options
     *   Key-value pairs of options.
     * @param array $paths
     *   File paths.
     */
    public function __construct(array $options = [], array $paths = [])
    {
        $this->setOptions($options);
        $this->setPaths($paths);
    }

    public function setOptions(array $options): self
    {
        foreach ($options as $name => $value) {
            switch ($name) {
                case 'assetJarMapping':
                    $this->setAssetJarMapping($value);
                    break;

                case 'workingDirectory':
                    $this->setWorkingDirectory($value);
                    break;

                case 'bundleGemFile':
                    $this->setBundleGemFile($value);
                    break;

                case 'bundleExecutable':
                    $this->setBundleExecutable($value);
                    break;

                case 'scssLintExecutable':
                    $this->setScssLintExecutable($value);
                    break;

                case 'failOn':
                    $this->setFailOn($value);
                    break;

                case 'failOnNoFiles':
                    $this->setFailOnNoFiles($value);
                    break;

                case 'lintReporters':
                    $this->setLintReporters($value);
                    break;

                case 'format':
                    $this->setFormat($value);
                    break;

                case 'require':
                    $this->setRequire($value);
                    break;

                case 'linters':
                    $this->setLinters($value);
                    break;

                case 'configFile':
                    $this->setConfigFile($value);
                    break;

                case 'exclude':
                    $this->setExclude($value);
                    break;

                case 'out':
                    $this->setOut($value);
                    break;

                case 'color':
                    $this->setColor($value);
                    break;

                case 'paths':
                    $this->setPaths($value);
                    break;
            }
        }

        return $this;
    }

    /**
     * The array key is the relevant value and the array value will be a boolean.
     *
     * @param string[]|bool[] $items
     *   Items.
     * @param bool $include
     *   Default value.
     *
     * @return bool[]
     *   Key is the relevant value, the value is a boolean.
     */
    protected function createIncludeList(array $items, bool $include): array
    {
        $item = reset($items);
        if (gettype($item) !== 'boolean') {
            $items = array_fill_keys($items, $include);
        }

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $lintReporters = $this->initLintReporters();
        if ($lintReporters && $this->getFormat() === '') {
            $this->setFormat('JSON');
        }

        $command = $this->getCommand();
        $this->printTaskInfo(sprintf('SCSS lint task runs: <info>%s</info>', $command));

        if ($lintReporters && $this->getFormat() !== 'JSON') {
            $this->exitCode = static::EXIT_CODE_INVALID;

            return new Result($this, $this->exitCode, $this->getExitMessage($this->exitCode));
        }

        /** @var Process $process */
        $process = new $this->processClass($command);

        $this->exitCode = $process->run();

        $numOfErrors = $this->exitCode;
        $numOfWarnings = 0;
        if ($this->isLintSuccess()) {
            $originalOutput = $process->getOutput();
            if ($this->getFormat() === 'JSON') {
                $jsonOutput = ($this->getOut() ? file_get_contents($this->getOut()) : $originalOutput);
                $reportWrapper = new ReportWrapper(json_decode($jsonOutput, true));

                $numOfErrors = $reportWrapper->numOfErrors();
                $numOfWarnings = $reportWrapper->numOfWarnings();

                if ($this->isReportHasToBePutBackIntoJar()) {
                    $this->setAssetJarValue('report', $reportWrapper);
                }

                foreach ($lintReporters as $lintReporter) {
                    $lintReporter
                        ->setReportWrapper($reportWrapper)
                        ->generate();
                }
            }

            if (!$lintReporters) {
                $this->output()->write($originalOutput);
            }
        }

        $exitCode = $this->getTaskExitCode($numOfErrors, $numOfWarnings);

        return new Result(
            $this,
            $exitCode,
            $this->getExitMessage($exitCode) ?: $process->getErrorOutput()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getCommand(): string
    {
        $options = $this->getCommandOptions();

        $cmdPattern = '';
        $cmdArgs = [];
        if ($this->getWorkingDirectory()) {
            $cmdPattern .= 'cd %s && ';
            $cmdArgs[] = escapeshellarg($this->getWorkingDirectory());
        }

        if ($this->getBundleGemFile()) {
            $cmdPattern .= 'BUNDLE_GEMFILE=%s ';
            $cmdArgs[] = escapeshellarg($this->getBundleGemFile());
        }

        if ($this->getBundleExecutable()) {
            $cmdPattern .= '%s exec ';
            $cmdArgs[] = escapeshellcmd($this->getBundleExecutable());
        }

        $cmdPattern .= escapeshellcmd($this->getScssLintExecutable());

        foreach ($options as $optionName => $optionValue) {
            switch ($this->options[$optionName]) {
                case 'value':
                    if ($optionValue) {
                        $cmdPattern .= " --$optionName=%s";
                        $cmdArgs[] = escapeshellarg($optionValue);
                    }
                    break;

                case 'multi-value':
                    $values = array_keys($optionValue, true, true);
                    $cmdPattern .= str_repeat(" --$optionName=%s", count($values));
                    foreach ($values as $value) {
                        $cmdArgs[] = escapeshellarg($value);
                    }
                    break;

                case 'list':
                    $values = array_keys($optionValue, true, true);
                    if ($values) {
                        $cmdPattern .= " --$optionName=%s";
                        $cmdArgs[] = escapeshellarg(implode(',', $values));
                    }
                    break;

                case 'tri-state':
                    if ($optionValue !== null) {
                        $cmdPattern .= $optionValue ? " --$optionName" : " --no-$optionName";
                    }
                    break;

                case 'include-exclude':
                    foreach (['include' => true, 'exclude' => false] as $optionNamePrefix => $filter) {
                        $values = array_keys($optionValue, $filter, true);
                        if ($values) {
                            $cmdPattern .= " --$optionNamePrefix-$optionName=%s";
                            $cmdArgs[] = escapeshellarg(implode(',', $values));
                        }
                    }
                    break;
            }
        }

        $paths = array_keys($this->getPaths(), true, true);
        if ($paths) {
            $cmdPattern .= ' --' . str_repeat(' %s', count($paths));
            foreach ($paths as $path) {
                $cmdArgs[] = escapeshellarg($path);
            }
        }

        return vsprintf($cmdPattern, $cmdArgs);
    }

    protected function getCommandOptions(): array
    {
        return [
            'format' => $this->getFormat(),
            'require' => $this->getRequire(),
            'linter' => $this->getLinters(),
            'config' => $this->getConfigFile(),
            'exclude' => $this->getExclude(),
            'out' =>  $this->getOut(),
            'color' => $this->getColor(),
        ];
    }

    protected function isReportHasToBePutBackIntoJar(): bool
    {
        return (
            $this->hasAssetJar()
            && $this->getAssetJarMap('report')
            && $this->isLintSuccess()
        );
    }

    /**
     * @return \Cheppers\LintReport\ReporterInterface[]
     */
    protected function initLintReporters(): array
    {
        $lintReporters = [];
        $c = $this->getContainer();
        foreach ($this->getLintReporters() as $id => $lintReporter) {
            if ($lintReporter === false) {
                continue;
            }

            if (!$lintReporter) {
                $lintReporter = $c->get($id);
            } elseif (is_string($lintReporter)) {
                $lintReporter = $c->get($lintReporter);
            }

            if ($lintReporter instanceof ReporterInterface) {
                $lintReporters[$id] = $lintReporter;
                if (!$lintReporter->getDestination()) {
                    $lintReporter
                        ->setFilePathStyle('relative')
                        ->setDestination($this->output());
                }
            }
        }

        return $lintReporters;
    }

    /**
     * Get the exit code regarding the failOn settings.
     */
    protected function getTaskExitCode(int $numOfErrors, int $numOfWarnings): int
    {
        if ($this->exitCode === static::EXIT_CODE_NO_FILES) {
            return ($this->getFailOnNoFiles() ? static::EXIT_CODE_NO_FILES : static::EXIT_CODE_OK);
        }

        if ($this->isLintSuccess()) {
            switch ($this->getFailOn()) {
                case 'never':
                    return static::EXIT_CODE_OK;

                case 'warning':
                    if ($numOfErrors) {
                        return static::EXIT_CODE_ERROR;
                    }

                    return $numOfWarnings ? static::EXIT_CODE_WARNING : static::EXIT_CODE_OK;

                case 'error':
                    return $numOfErrors ? static::EXIT_CODE_ERROR : static::EXIT_CODE_OK;
            }
        }

        return $this->exitCode;
    }

    protected function getExitMessage(int $exitCode): ?string
    {
        if (isset($this->exitMessages[$exitCode])) {
            return $this->exitMessages[$exitCode];
        }

        return null;
    }

    /**
     * Returns true if the lint ran successfully.
     *
     * Returns true even if there was any code style error or warning.
     */
    protected function isLintSuccess(): bool
    {
        return in_array($this->exitCode, $this->lintSuccessExitCodes());
    }

    /**
     * @return int[]
     */
    protected function lintSuccessExitCodes(): array
    {
        return [
            static::EXIT_CODE_OK,
            static::EXIT_CODE_NO_FILES,
            static::EXIT_CODE_WARNING,
            static::EXIT_CODE_ERROR,
        ];
    }
}
