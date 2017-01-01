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
use Robo\Contract\OutputAwareInterface;
use Robo\Result;
use Robo\Task\BaseTask;
use Symfony\Component\Process\Process;

class Run extends BaseTask implements
    AssetJarAwareInterface,
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

    /**
     * Directory to step in before run the `scss-lint`.
     *
     * @var string
     */
    protected $workingDirectory = '';

    /**
     * Severity level.
     *
     * @var bool
     */
    protected $failOn = 'error';

    /**
     * Fail if there is no SCSS file to lint.
     *
     * @var bool
     */
    protected $failOnNoFiles = false;

    /**
     * @var \Cheppers\LintReport\ReporterInterface[]
     */
    protected $lintReporters = [];

    /**
     * Specify how to display lints.
     *
     * @var string
     */
    protected $format = '';

    /**
     * Required Ruby files.
     *
     * @var array
     */
    protected $requires = [];

    /**
     * Linters to include or exclude.
     *
     * @var array
     */
    protected $linters = [];

    /**
     * Config file path.
     *
     * @var string
     */
    protected $configFile = null;

    /**
     * SCSS files to exclude.
     *
     * @var array
     */
    protected $exclude = [];

    /**
     * Write output to a file instead of STDOUT.
     *
     * @var string
     */
    protected $out = '';

    /**
     * Force output to be colorized.
     *
     * @var bool|null
     */
    protected $colorize = null;

    /**
     * SCSS files to check.
     *
     * @var array
     */
    protected $paths = [];

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
        $this->options($options);
        $this->paths($paths);
    }

    /**
     * All in one configuration.
     *
     * @return $this
     */
    public function options(array $options)
    {
        foreach ($options as $name => $value) {
            switch ($name) {
                case 'assetJarMapping':
                    $this->setAssetJarMapping($value);
                    break;

                case 'workingDirectory':
                    $this->workingDirectory($value);
                    break;

                case 'failOn':
                    $this->failOn($value);
                    break;

                case 'failOnNoFiles':
                    $this->failOnNoFiles($value);
                    break;

                case 'lintReporters':
                    $this->setLintReporters($value);
                    break;

                case 'format':
                    $this->format($value);
                    break;

                case 'requires':
                    $this->requires($value);
                    break;

                case 'linters':
                    $this->linters($value);
                    break;

                case 'configFile':
                    $this->configFile($value);
                    break;

                case 'exclude':
                    $this->exclude($value);
                    break;

                case 'out':
                    $this->out($value);
                    break;

                case 'color':
                    $this->color($value);
                    break;

                case 'paths':
                    $this->paths($value);
                    break;
            }
        }

        return $this;
    }

    /**
     * Set the current working directory.
     *
     * @return $this
     */
    public function workingDirectory(string $value)
    {
        $this->workingDirectory = $value;

        return $this;
    }

    /**
     * Fail if there is a lint with warning severity.
     *
     * @param string $value
     *   Allowed values are: never, warning, error.
     *
     * @return $this
     */
    public function failOn(string $value)
    {
        $this->failOn = $value;

        return $this;
    }

    /**
     * Fail if there is no SCSS file to lint.
     *
     * @return $this
     */
    public function failOnNoFiles(bool $value)
    {
        $this->failOnNoFiles = $value;

        return $this;
    }

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
    public function format(string $value)
    {
        $this->format = $value;

        return $this;
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
    public function requires($gems, bool $include = true)
    {
        if (!is_array($gems)) {
            $gems = [$gems => $include];
        }

        $this->requires = $this->createIncludeList($gems, $include) + $this->requires;

        return $this;
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
    public function linters($names, bool $include = true)
    {
        if (!is_array($names)) {
            $names = [$names => $include];
        }

        $this->linters = $this->createIncludeList($names, $include) + $this->linters;

        return $this;
    }

    /**
     * Specify which configuration file you want to use.
     *
     * @return $this
     */
    public function configFile(string $path)
    {
        $this->configFile = $path;

        return $this;
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
    public function exclude($filePaths, bool $include = true)
    {
        if (!is_array($filePaths)) {
            $filePaths = [$filePaths => $include];
        }

        $this->exclude = $this->createIncludeList($filePaths, $include) + $this->exclude;

        return $this;
    }

    /**
     * Write output to a file instead of STDOUT.
     *
     * @param string|null $filePath
     *
     * @return $this
     */
    public function out(string $filePath)
    {
        $this->out = $filePath;

        return $this;
    }

    /**
     * Force output to be colorized.
     *
     * @param bool|null $colorize
     *
     * @return $this
     */
    public function color(?bool $colorize)
    {
        $this->colorize = $colorize;

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
     * File paths to lint.
     *
     * @param string|string[]|bool[] $paths
     *   Key-value pair of file names and boolean.
     * @param bool $include
     *   Exclude or include the files in $paths.
     *
     * @return $this
     */
    public function paths(array $paths, $include = true)
    {
        $this->paths = $this->createIncludeList($paths, $include) + $this->paths;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $command = $this->buildCommand();
        $this->printTaskInfo(sprintf('SCSS lint task runs: <info>%s</info>', $command));

        $lintReporters = $this->initLintReporters();
        if ($lintReporters && $this->format !== 'JSON') {
            $this->exitCode = static::EXIT_CODE_INVALID;

            return new Result($this, $this->exitCode, $this->getExitMessage($this->exitCode));
        }

        /** @var Process $process */
        $process = new $this->processClass($command);
        if ($this->workingDirectory) {
            $process->setWorkingDirectory($this->workingDirectory);
        }

        $this->startTimer();
        $this->exitCode = $process->run();
        $this->stopTimer();

        $numOfErrors = $this->exitCode;
        $numOfWarnings = 0;
        if ($this->isLintSuccess()) {
            $originalOutput = $process->getOutput();
            if ($this->format === 'JSON') {
                $jsonOutput = ($this->out ? file_get_contents($this->out) : $originalOutput);
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
            $this->getExitMessage($exitCode) ?: $process->getErrorOutput(),
            [
                'time' => $this->getExecutionTime(),
            ]
        );
    }

    /**
     * Build the CLI command based on the configuration.
     */
    public function buildCommand(): string
    {
        $cmd_pattern = 'bundle exec scss-lint';
        $cmd_args = [];

        if ($this->format) {
            $cmd_pattern .= ' --format=%s';
            $cmd_args[] = escapeshellarg($this->format);
        }

        $gems = array_keys($this->requires, true, true);
        $cmd_pattern .= str_repeat(' --require=%s', count($gems));
        foreach ($gems as $gem) {
            $cmd_args[] = escapeshellarg($gem);
        }

        foreach (['include' => true, 'exclude' => false] as $name => $filter) {
            $linters = array_keys($this->linters, $filter, true);
            if ($linters) {
                $cmd_pattern .= " --$name-linter=%s";
                $cmd_args[] = escapeshellarg(implode(',', $linters));
            }
        }

        if ($this->configFile) {
            $cmd_pattern .= ' --config=%s';
            $cmd_args[] = escapeshellarg($this->configFile);
        }

        $exclude = array_keys($this->exclude, true, true);
        if ($exclude) {
            $cmd_pattern .= ' --exclude=%s';
            $cmd_args[] = escapeshellarg(implode(',', $exclude));
        }

        if ($this->out) {
            $cmd_pattern .= ' --out=%s';
            $cmd_args[] = escapeshellarg($this->out);
        }

        if ($this->colorize !== null) {
            $cmd_pattern .= $this->colorize ? ' --color' : ' --no-color';
        }

        $paths = array_keys($this->paths, true, true);
        if ($paths) {
            $cmd_pattern .= ' --' . str_repeat(' %s', count($paths));
            foreach ($paths as $path) {
                $cmd_args[] = escapeshellarg($path);
            }
        }

        return vsprintf($cmd_pattern, $cmd_args);
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
            return ($this->failOnNoFiles ? static::EXIT_CODE_NO_FILES : static::EXIT_CODE_OK);
        }

        if ($this->isLintSuccess()) {
            switch ($this->failOn) {
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
