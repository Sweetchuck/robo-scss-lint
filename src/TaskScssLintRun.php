<?php

namespace Cheppers\Robo\Task\ScssLint;

use Cheppers\AssetJar\AssetJarAware;
use Cheppers\AssetJar\AssetJarAwareInterface;
use Robo\Common\IO;
use Robo\Result;
use Robo\Task\BaseTask;
use Symfony\Component\Process\Process;

/**
 * Class TaskScssLintRun.
 *
 * Assert mapping:
 *   - report: Parsed JSON lint report.
 *
 * @package Cheppers\Robo\Task\ScssLint
 */
class TaskScssLintRun extends BaseTask implements AssetJarAwareInterface
{

    use AssetJarAware;
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
     * @param array $options
     *   Options.
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
     * @param string $value
     *   Directory path.
     *
     * @return $this
     */
    public function workingDirectory($value)
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
    public function failOn($value)
    {
        $this->failOn = $value;

        return $this;
    }

    /**
     * Fail if there is no SCSS file to lint.
     *
     * @param bool $value
     *   Default: false.
     *
     * @return $this
     */
    public function failOnNoFiles($value)
    {
        $this->failOnNoFiles = $value;

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
    public function format($value)
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
    public function requires($gems, $include = true)
    {
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
    public function linters($names, $include = true)
    {
        $this->linters = $this->createIncludeList($names, $include) + $this->linters;

        return $this;
    }

    /**
     * Specify which configuration file you want to use.
     *
     * @param string $path
     *   File path.
     *
     * @return $this
     */
    public function configFile($path)
    {
        $this->configFile = $path;

        return $this;
    }

    /**
     * List of file names to exclude.
     *
     * @param string|string[]|bool[] $file_paths
     *   File names.
     * @param bool $include
     *   If TRUE $file_paths will be added to the exclude list.
     *
     * @return $this
     */
    public function exclude($file_paths, $include = true)
    {
        $this->exclude = $this->createIncludeList($file_paths, $include) + $this->exclude;

        return $this;
    }

    /**
     * Write output to a file instead of STDOUT.
     *
     * @param string|null $file_path
     *
     * @return $this
     */
    public function out($file_path)
    {
        $this->out = $file_path;

        return $this;
    }

    /**
     * Force output to be colorized.
     *
     * @param bool|null $colorize
     *
     * @return $this
     */
    public function color($colorize)
    {
        $this->colorize = $colorize;

        return $this;
    }

    /**
     * The array key is the relevant value and the array value will be a boolean.
     *
     * @param string|string[]|bool[] $items
     *   Items.
     * @param bool $include
     *   Default value.
     *
     * @return bool[]
     *   Key is the relevant value, the value is a boolean.
     */
    protected function createIncludeList($items, $include)
    {
        if (!is_array($items)) {
            $items = [$items => $include];
        }

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
     * TaskScssLintRun class run function.
     */
    public function run()
    {
        $command = $this->buildCommand();

        $this->printTaskInfo(sprintf('ScssLint task runs: <info>%s</info>', $command));

        /** @var Process $process */
        $process = new $this->processClass($command);
        if ($this->workingDirectory) {
            $process->setWorkingDirectory($this->workingDirectory);
        }

        $process->run();

        $this->exitCode = $process->getExitCode();

        $write_output = true;

        $report_parents = $this->getAssetJarMap('report');
        if ($this->hasAssetJar()
            && $report_parents
            && $this->format === 'JSON'
            && in_array($this->exitCode, $this->lintSuccessExitCodes())
        ) {
            $report = ($this->exitCode === static::EXIT_CODE_NO_FILES ? [] : json_decode($process->getOutput(), true));

            $write_output = false;
            $this->setAssetJarValue('report', $report);
        }

        if ($write_output) {
            $this->getOutput()->writeln($process->getOutput());
        }

        $message = isset($this->exitMessages[$this->exitCode]) ?
            $this->exitMessages[$this->exitCode]
            : $process->getErrorOutput();

        return new Result(
            $this,
            $this->getTaskExitCode(),
            $message
        );
    }

    /**
     * Build the CLI command based on the configuration.
     *
     * @return string
     *   CLI command to execute.
     */
    public function buildCommand()
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

    /**
     * Get the exit code regarding the failOn settings.
     *
     * @return int
     *   Exit code.
     */
    public function getTaskExitCode()
    {
        if ($this->exitCode === static::EXIT_CODE_NO_FILES) {
            return ($this->failOnNoFiles ? static::EXIT_CODE_NO_FILES : static::EXIT_CODE_OK);
        }

        $tolerance = [
            'never' => [static::EXIT_CODE_ERROR, static::EXIT_CODE_WARNING],
            'error' => [static::EXIT_CODE_WARNING],
        ];

        if (isset($tolerance[$this->failOn]) && in_array($this->exitCode, $tolerance[$this->failOn])) {
            return static::EXIT_CODE_OK;
        }

        return $this->exitCode;
    }

    /**
     * @return int[]
     */
    protected function lintSuccessExitCodes()
    {
        return [
            static::EXIT_CODE_OK,
            static::EXIT_CODE_NO_FILES,
            static::EXIT_CODE_WARNING,
            static::EXIT_CODE_ERROR,
        ];
    }
}
