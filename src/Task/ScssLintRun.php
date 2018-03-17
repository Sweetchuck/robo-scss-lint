<?php

namespace Sweetchuck\Robo\ScssLint\Task;

use Robo\TaskInfo;
use Sweetchuck\LintReport\ReporterInterface;
use Sweetchuck\Robo\ScssLint\LintReportWrapper\ReportWrapper;
use Sweetchuck\Robo\ScssLint\Utils;
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

class ScssLintRun extends BaseTask implements
    CommandInterface,
    ContainerAwareInterface,
    BuilderAwareInterface,
    OutputAwareInterface
{
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

    const EXIT_CODE_UNKNOWN = 4;

    /**
     * No SCSS files matched by the patterns.
     */
    const EXIT_CODE_NO_FILES = 80;

    /**
     * @var string
     */
    protected $taskName = '';

    /**
     * @todo Some kind of dependency injection would be awesome.
     *
     * @var string
     */
    protected $processClass = Process::class;

    // region Options

    // region assetNamePrefix
    /**
     * @var string
     */
    protected $assetNamePrefix = '';

    public function getAssetNamePrefix(): string
    {
        return $this->assetNamePrefix;
    }

    /**
     * @return $this
     */
    public function setAssetNamePrefix(string $value)
    {
        $this->assetNamePrefix = $value;

        return $this;
    }
    // endregion

    // region workingDirectory
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
    // endregion

    // region envVarPath
    /**
     * @var array
     */
    protected $envVarPath = [];

    public function getEnvVarPath(): array
    {
        return $this->envVarPath;
    }

    /**
     * @return $this
     */
    public function setEnvVarPath(array $paths)
    {
        $this->envVarPath = $this->createIncludeList($paths, true);

        return $this;
    }

    /**
     * @return $this
     */
    public function addEnvVarPath(string $path)
    {
        $this->envVarPath[$path] = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function removeEnvVarPath(string $path)
    {
        unset($this->envVarPath[$path]);

        return $this;
    }
    // endregion

    // region envVarBundleGemFile
    /**
     * @var string
     */
    protected $envVarBundleGemFile = '';

    public function getEnvVarBundleGemFile(): string
    {
        return $this->envVarBundleGemFile;
    }

    /**
     * @return $this
     */
    public function setEnvVarBundleGemFile(string $envVarBundleGemFile)
    {
        $this->envVarBundleGemFile = $envVarBundleGemFile;

        return $this;
    }
    // endregion

    // region rubyExecutable
    /**
     * @var string
     */
    protected $rubyExecutable = '';

    public function getRubyExecutable(): string
    {
        return $this->rubyExecutable;
    }

    /**
     * @return $this
     */
    public function setRubyExecutable(string $value)
    {
        $this->rubyExecutable = $value;

        return $this;
    }
    // endregion

    // region bundleExecutable
    /**
     * @var string
     */
    protected $bundleExecutable = 'bundle';

    public function getBundleExecutable(): string
    {
        return $this->bundleExecutable;
    }

    /**
     * @return $this
     */
    public function setBundleExecutable(string $value)
    {
        $this->bundleExecutable = $value;

        return $this;
    }
    // endregion

    // region scssLintExecutable
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
    // endregion

    // region failOn
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
    // endregion

    // region failOnNoFiles
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
    // endregion

    // region lintReporters
    /**
     * @var \Sweetchuck\LintReport\ReporterInterface[]
     */
    protected $lintReporters = [];

    /**
     * @return \Sweetchuck\LintReport\ReporterInterface[]
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
     * @param string|\Sweetchuck\LintReport\ReporterInterface $lintReporter
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
    // endregion

    // region format
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
    // endregion

    // region requires
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
    // endregion

    // region linters
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
    // endregion

    // region configFile
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
    // endregion

    // region exclude
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
    // endregion

    // region out
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
    // endregion

    // region colorize
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
    // endregion

    // region paths
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
     *
     * @return $this
     */
    public function setPaths(array $paths)
    {
        $this->paths = $paths;

        return $this;
    }
    // endregion

    // endregion

    /**
     * @var array
     */
    protected $assets = [
        'report' => null,
    ];

    /**
     * Process exit code.
     *
     * @var int
     */
    protected $lintExitCode = 0;

    /**
     * @var string
     */
    protected $lintStdOutput = '';

    /**
     * @var bool
     */
    protected $isLintStdOutputPublic = true;

    /**
     * @var string
     */
    protected $machineReadableFormat = 'JSON';

    /**
     * @var string
     */
    protected $reportRaw = '';

    /**
     * @var bool
     */
    protected $addFilesToCliCommand = true;

    /**
     * @var array
     */
    protected $report = [];

    /**
     * @var \Sweetchuck\LintReport\ReportWrapperInterface
     */
    protected $reportWrapper = null;

    /**
     * Exit code and error message mapping.
     *
     * @var string
     */
    protected $exitMessages = [
        0 => 'No lints were found',
        1 => 'Lints with a severity of warning were reported (no errors)',
        2 => 'One or more errors were reported (and any number of warnings)',
        3 => 'Extra lint reporters can be used only if the output format is "JSON".',
        64 => 'Command line usage error (invalid flag, etc.)',
        66 => 'One or more files specified were not found',
        69 => 'Required library specified via -r/--require flag was not found',
        70 => 'Unexpected error (i.e. a bug); please report it',
        78 => 'Invalid configuration file; your YAML is likely incorrect',
        80 => 'Files glob patterns specified did not match any files.',
    ];

    /**
     * @var string
     */
    protected $cmdPattern = '';

    /**
     * @var array
     */
    protected $cmdArgs = [];

    /**
     * @var array
     */
    protected $cmdOptions = [];

    /**
     * @return $this
     */
    public function setOptions(array $options)
    {
        foreach ($options as $name => $value) {
            switch ($name) {
                case 'assetNamePrefix':
                    $this->setAssetNamePrefix($value);
                    break;

                case 'workingDirectory':
                    $this->setWorkingDirectory($value);
                    break;

                case 'envVarPath':
                    $this->setEnvVarPath($value);
                    break;

                case 'envVarBundleGemFile':
                    $this->setEnvVarBundleGemFile($value);
                    break;

                case 'rubyExecutable':
                    $this->setRubyExecutable($value);
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
            $this->isLintStdOutputPublic = false;
            $this->setFormat($this->machineReadableFormat);
        }

        if ($lintReporters && $this->getFormat() !== $this->machineReadableFormat) {
            return new Result(
                $this,
                static::EXIT_CODE_INVALID,
                $this->getExitMessage(static::EXIT_CODE_INVALID)
            );
        }

        return $this
            ->runHeader()
            ->runLint()
            ->runReleaseLintReports()
            ->runReturn();
    }

    /**
     * @return $this
     */
    protected function runHeader()
    {
        $this->printTaskInfo('', null);

        return $this;
    }

    /**
     * @return $this
     */
    protected function runLint()
    {
        $this->reportRaw = '';
        $this->report = [];
        $this->reportWrapper = null;
        $this->lintExitCode = static::EXIT_CODE_OK;

        /** @var \Symfony\Component\Process\Process $process */
        $process = new $this->processClass($this->getCommand());

        $this->lintExitCode = $process->run();
        $this->lintStdOutput = $process->getOutput();

        if ($this->isLintSuccess() && $this->getFormat() === $this->machineReadableFormat) {
            $out = $this->getOut();
            if (!$out) {
                $this->reportRaw = $this->lintStdOutput;
            } elseif (is_readable($out)) {
                $this->reportRaw = file_get_contents($out);
            }
        }

        if ($this->reportRaw) {
            // @todo Pray for a valid JSON output.
            $this->report = (array) json_decode($this->reportRaw, true);
            $this->reportWrapper = new ReportWrapper($this->report);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function runReleaseLintReports()
    {
        if ($this->isLintStdOutputPublic) {
            $this
                ->output()
                ->write($this->lintStdOutput);
        }

        if ($this->reportWrapper) {
            foreach ($this->initLintReporters() as $lintReporter) {
                $lintReporter
                    ->setReportWrapper($this->reportWrapper)
                    ->generate();
            }
        }

        return $this;
    }

    protected function runReturn(): Result
    {
        $exitCode = $this->reportWrapper ?
            $this->getTaskExitCode(
                $this->reportWrapper->numOfErrors(),
                $this->reportWrapper->numOfWarnings()
            )
            : $this->lintExitCode;

        $this->assets['report'] = $this->reportWrapper;

        return new Result(
            $this,
            $exitCode,
            $this->getExitMessage($exitCode),
            $this->getAssetsWithPrefixedNames()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getCommand(): string
    {
        return $this
            ->getCommandInit()
            ->getCommandChangeDirectory()
            ->getCommandPrefix()
            ->getCommandEnvironmentVariables()
            ->getCommandRuby()
            ->getCommandBundle()
            ->getCommandScssLintExecutable()
            ->getCommandScssLintOptions()
            ->getCommandBuild();
    }

    /**
     * @return $this
     */
    protected function getCommandInit()
    {
        $this->cmdPattern = '';
        $this->cmdArgs = [];
        $this->cmdOptions = $this->getCommandOptions();

        return $this;
    }

    /**
     * @return $this
     */
    protected function getCommandChangeDirectory()
    {
        if ($this->cmdOptions['workingDirectory']['value']) {
            $this->cmdPattern .= 'cd %s && ';
            $this->cmdArgs[] = escapeshellarg($this->cmdOptions['workingDirectory']['value']);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function getCommandPrefix()
    {
        return $this;
    }

    protected function getCommandEnvironmentVariables()
    {
        $paths = Utils::filterEnabled($this->cmdOptions['envVarPath']['value']);
        if ($paths) {
            $this->cmdPattern .= 'PATH=%s ';
            $this->cmdArgs[] = escapeshellarg(implode(':', $paths));
        }

        if ($this->cmdOptions['envVarBundleGemFile']['value']) {
            $this->cmdPattern .= 'BUNDLE_GEMFILE=%s ';
            $this->cmdArgs[] = escapeshellarg($this->cmdOptions['envVarBundleGemFile']['value']);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function getCommandRuby()
    {
        if ($this->cmdOptions['rubyExecutable']['value']) {
            $this->cmdPattern .= '%s ';
            $this->cmdArgs[] = escapeshellcmd($this->cmdOptions['rubyExecutable']['value']);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function getCommandBundle()
    {
        if ($this->cmdOptions['bundleExecutable']['value']) {
            $this->cmdPattern .= '%s exec ';
            $this->cmdArgs[] = escapeshellcmd($this->cmdOptions['bundleExecutable']['value']);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function getCommandScssLintExecutable()
    {
        $this->cmdPattern .= '%s';
        $this->cmdArgs[] = escapeshellcmd($this->cmdOptions['scssLintExecutable']['value']);

        return $this;
    }

    /**
     * @return $this
     */
    protected function getCommandScssLintOptions()
    {
        foreach ($this->cmdOptions as $optionName => $option) {
            $optionNameCli = $option['cliName'] ?? $optionName;
            switch ($option['type']) {
                case 'value':
                    if ($option['value']) {
                        $this->cmdPattern .= " --$optionNameCli=%s";
                        $this->cmdArgs[] = escapeshellarg($option['value']);
                    }
                    break;

                case 'multi-value':
                    $values = array_keys($option['value'], true, true);
                    $this->cmdPattern .= str_repeat(" --$optionNameCli=%s", count($values));
                    foreach ($values as $value) {
                        $this->cmdArgs[] = escapeshellarg($value);
                    }
                    break;

                case 'list':
                    $values = array_keys($option['value'], true, true);
                    if ($values) {
                        $this->cmdPattern .= " --$optionNameCli=%s";
                        $this->cmdArgs[] = escapeshellarg(implode(',', $values));
                    }
                    break;

                case 'tri-state':
                    if ($option['value'] !== null) {
                        $this->cmdPattern .= $option['value'] ? " --$optionNameCli" : " --no-$optionNameCli";
                    }
                    break;

                case 'include-exclude':
                    foreach (['include' => true, 'exclude' => false] as $optionNamePrefix => $filter) {
                        $values = array_keys($option['value'], $filter, true);
                        if ($values) {
                            $this->cmdPattern .= " --$optionNamePrefix-$optionNameCli=%s";
                            $this->cmdArgs[] = escapeshellarg(implode(',', $values));
                        }
                    }
                    break;
            }
        }

        if ($this->addFilesToCliCommand) {
            $paths = Utils::filterEnabled($this->getPaths());
            if ($paths) {
                $this->cmdPattern .= ' --' . str_repeat(' %s', count($paths));
                foreach ($paths as $path) {
                    $this->cmdArgs[] = escapeshellarg($path);
                }
            }
        }

        return $this;
    }

    protected function getCommandBuild(): string
    {
        return vsprintf($this->cmdPattern, $this->cmdArgs);
    }

    protected function getCommandOptions(): array
    {
        return [
            'workingDirectory' => [
                'type' => 'other',
                'value' => $this->getWorkingDirectory(),
            ],
            'envVarPath' => [
                'type' => 'other',
                'name' => 'PATH',
                'value' => $this->getEnvVarPath(),
            ],
            'envVarBundleGemFile' => [
                'type' => 'other',
                'value' => $this->getEnvVarBundleGemFile(),
            ],
            'rubyExecutable' => [
                'type' => 'other',
                'value' => $this->getRubyExecutable(),
            ],
            'bundleExecutable' => [
                'type' => 'other',
                'value' => $this->getBundleExecutable(),
            ],
            'scssLintExecutable' => [
                'type' => 'other',
                'value' => $this->getScssLintExecutable(),
            ],
            'format' => [
                'type' => 'value',
                'value' => $this->getFormat(),
            ],
            'require' => [
                'type' => 'multi-value',
                'value' => $this->getRequire(),
            ],
            'linter' => [
                'type' => 'include-exclude',
                'value' => $this->getLinters(),
            ],
            'config' => [
                'type' => 'value',
                'value' => $this->getConfigFile(),
            ],
            'exclude' => [
                'type' => 'list',
                'value' => $this->getExclude(),
            ],
            'out' => [
                'type' =>  'value',
                'value' =>  $this->getOut(),
            ],
            'color' => [
                'type' => 'tri-state',
                'value' => $this->getColor(),
            ],
        ];
    }

    protected function getAssetsWithPrefixedNames(): array
    {
        $prefix = $this->getAssetNamePrefix();
        if (!$prefix) {
            return $this->assets;
        }

        $data = [];
        foreach ($this->assets as $key => $value) {
            $data["{$prefix}{$key}"] = $value;
        }

        return $data;
    }

    /**
     * @return \Sweetchuck\LintReport\ReporterInterface[]
     */
    protected function initLintReporters(): array
    {
        $lintReporters = [];
        $container = $this->getContainer();
        foreach ($this->getLintReporters() as $id => $lintReporter) {
            if ($lintReporter === false) {
                continue;
            }

            if (!$lintReporter) {
                $lintReporter = $container->get($id);
            } elseif (is_string($lintReporter)) {
                $lintReporter = $container->get($lintReporter);
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
        if ($this->lintExitCode === static::EXIT_CODE_NO_FILES) {
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

        return $this->lintExitCode;
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
        return in_array($this->lintExitCode, $this->lintSuccessExitCodes());
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

    /**
     * {@inheritdoc}
     */
    protected function printTaskInfo($text, $context = null)
    {
        parent::printTaskInfo($text ?: $this->getTaskInfoPattern(), $context);
    }

    /**
     * @return string
     */
    protected function getTaskInfoPattern()
    {
        return 'is linting files';
    }

    public function getTaskName(): string
    {
        return $this->taskName ?: TaskInfo::formatTaskName($this);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTaskContext($context = null)
    {
        if (!$context) {
            $context = [];
        }

        if (empty($context['name'])) {
            $context['name'] = $this->getTaskName();
        }

        return parent::getTaskContext($context);
    }
}
