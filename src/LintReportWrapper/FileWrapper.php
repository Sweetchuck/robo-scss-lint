<?php

namespace Cheppers\Robo\ScssLint\LintReportWrapper;

use Cheppers\LintReport\FileWrapperInterface;
use Cheppers\LintReport\ReportWrapperInterface;

class FileWrapper implements FileWrapperInterface
{
    /**
     * @var array
     */
    protected $file = [];

    /**
     * {@inheritdoc}
     */
    public function __construct(array $file)
    {
        $this->file = $file + [
            'filePath' => '',
            'errors' => 0,
            'warnings' => 0,
            'stats' => [],
            'failures' => [],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function filePath(): string
    {
        return $this->file['filePath'];
    }

    /**
     * {@inheritdoc}
     */
    public function numOfErrors()
    {
        return $this->file['errors'];
    }

    /**
     * {@inheritdoc}
     */
    public function numOfWarnings(): int
    {
        return $this->file['warnings'];
    }

    /**
     * {@inheritdoc}
     */
    public function yieldFailures()
    {
        foreach ($this->file['failures'] as $failure) {
            yield new FailureWrapper($failure);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function stats(): array
    {
        if (!$this->file['stats']) {
            $this->file['stats'] = [
                'severity' => 'ok',
                'has' => [
                    ReportWrapperInterface::SEVERITY_OK => false,
                    ReportWrapperInterface::SEVERITY_WARNING => false,
                    ReportWrapperInterface::SEVERITY_ERROR => false,
                ],
                'source' => [],
            ];
            foreach ($this->file['failures'] as $failure) {
                if ($this->severityComparer($this->file['stats']['severity'], $failure['severity']) < 0) {
                    $this->file['stats']['severity'] = $failure['severity'];
                }

                $this->file['stats']['has'][$failure['severity']] = true;

                $this->file['stats']['source'] += [
                    $failure['linter'] => [
                        'severity' => $failure['severity'],
                        'count' => 0,
                    ],
                ];
                $this->file['stats']['source'][$failure['linter']]['count']++;
            }
        }

        return $this->file['stats'];
    }

    /**
     * {@inheritdoc}
     */
    public function highestSeverity(): string
    {
        if ($this->numOfErrors()) {
            return ReportWrapperInterface::SEVERITY_ERROR;
        }

        if ($this->numOfWarnings()) {
            return ReportWrapperInterface::SEVERITY_WARNING;
        }

        return ReportWrapperInterface::SEVERITY_OK;
    }

    /**
     * {@inheritdoc}
     */
    protected function severityComparer(string $a, string $b): int
    {
        if ($a === $b) {
            return 0;
        }

        $weights = [
            ReportWrapperInterface::SEVERITY_OK => 1,
            ReportWrapperInterface::SEVERITY_WARNING => 2,
            ReportWrapperInterface::SEVERITY_ERROR => 3,
        ];

        $aWeight = $weights[$a] ?? 0;
        $bWeight = $weights[$b] ?? 0;

        return $aWeight <=> $bWeight;
    }
}
