<?php

namespace Sweetchuck\Robo\ScssLint\Task;

use Sweetchuck\Robo\ScssLint\LintReportWrapper\ReportWrapper;

class ScssLintRunInput extends ScssLintRun
{
    /**
     * {@inheritdoc}
     */
    protected $addFilesToCliCommand = false;

    /**
     * {@inheritdoc}
     */
    protected $isLintStdOutputPublic = false;

    /**
     * @var array
     */
    protected $currentFile = [
        'fileName' => '',
        'content' => '',
    ];

    //region Option - stdinFilePath
    /**
     * @var string
     */
    protected $stdinFilePath = '';

    public function getStdinFilePath(): string
    {
        return $this->stdinFilePath;
    }

    /**
     * @return $this
     */
    public function setStdinFilePath(string $value)
    {
        $this->stdinFilePath = $value;

        return $this;
    }
    //endregion

    public function __construct(array $options = [])
    {
        $this->options['stdin-file-path'] = 'value';

        parent::__construct($options);
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options)
    {
        parent::setOptions($options);
        foreach ($options as $name => $value) {
            switch ($name) {
                case 'stdinFilePath':
                    $this->setStdinFilePath($value);
                    break;
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function runLint()
    {
        $reports = [];
        $files = $this->getJarValueOrLocal('paths');
        $backupFailOn = $this->getFailOn();

        $this->setFailOn('never');
        foreach ($files as $fileName => $file) {
            if (!is_array($file)) {
                $file = [
                    'fileName' => $fileName,
                    'content' => $file,
                ];
            }

            $this->currentFile = $file;

            $this->setStdinFilePath($fileName);
            $lintExitCode = $this->lintExitCode;
            parent::runLint();
            $this->lintExitCode = max($lintExitCode, $this->lintExitCode);

            if ($this->report) {
                $reports += $this->report;
            }
        }
        $this->setFailOn($backupFailOn);

        $this->report = $reports;
        if ($this->report) {
            $this->reportRaw = json_encode($this->report);
            $this->reportWrapper = new ReportWrapper($this->report);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCommand(): string
    {
        if ($this->currentFile['content'] === null) {
            // @todo Handle the different working directories.
            $echo = $this->currentFile['command'];
        } else {
            $echo = sprintf('echo -n %s', escapeshellarg($this->currentFile['content']));
        }

        return $echo . ' | ' . parent::getCommand();
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommandOptions(): array
    {
        return [
            'stdin-file-path' => $this->currentFile['fileName'] ?? $this->getStdinFilePath(),
        ] + parent::getCommandOptions();
    }

    /**
     * @return mixed|null
     */
    protected function getJarValueOrLocal(string $itemName)
    {
        $map = $this->getAssetJarMap($itemName);
        if ($map) {
            $value = $this->getAssetJarValue($itemName, $keyExists);
            if ($keyExists) {
                return $value;
            }
        }

        switch ($itemName) {
            case 'paths':
                return $this->getPaths();
        }

        return null;
    }

    /**
     * @return string
     */
    protected function getTaskInfoPattern()
    {
        return "{name} is linting <info>{count}</info> files from StdInput";
    }

    /**
     * {@inheritdoc}
     */
    protected function getTaskContext($context = null)
    {
        return [
            'count' => count($this->getJarValueOrLocal('paths')),
        ] + parent::getTaskContext($context);
    }
}
