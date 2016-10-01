<?php

namespace Cheppers\Robo\ScssLint\LintReportWrapper;

use Cheppers\LintReport\ReportWrapperInterface;

/**
 * Class ReportWrapper.
 *
 * @package Cheppers\LintReport\Wrapper\ScssLint
 */
class ReportWrapper implements ReportWrapperInterface
{

    /**
     * @param array $a
     * @param array $b
     *
     * @return int
     */
    public static function failureComparer(array $a, array $b)
    {
        $fields = [
            'line' => 0,
            'column' => 0,
            'length' => 0,
            'reason' => '',
        ];

        $a += $fields;
        $b += $fields;

        foreach (array_keys($fields) as $field) {
            if ($a[$field] === $b[$field]) {
                continue;
            }

            switch ($field) {
                case 'line':
                case 'column':
                case 'length':
                    return $a[$field] < $b[$field] ? -1 : 1;

                default:
                    return strcmp($a[$field], $b[$field]);
            }
        }

        return 0;
    }

    /**
     * @var array
     */
    protected $report = [];

    /**
     * @var array
     */
    protected $reportInternal = [];

    /**
     * @var int|null
     */
    protected $numOfErrors = null;

    /**
     * @var int|null
     */
    protected $numOfWarnings = null;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $report = null)
    {
        if ($report !== null) {
            $this->setReport($report);
        }
    }

    /**
     * @return array
     */
    public function getReport()
    {
        return $this->report;
    }

    /**
     * @param array $report
     *
     * @return $this
     */
    public function setReport($report)
    {
        $this->report = $report;
        $this->reportInternal = [];
        $this->numOfErrors = 0;
        $this->numOfWarnings = 0;

        foreach ($report as $filePath => $failures) {
            usort($failures, [static::class, 'failureComparer']);

            $this->reportInternal[$filePath] = [
                'filePath' => $filePath,
                'errors' => 0,
                'warnings' => 0,
                'stats' => [],
                'failures' => $failures,
            ];

            foreach ($failures as $failure) {
                if ($failure['severity'] === 'error') {
                    $this->reportInternal[$filePath]['errors']++;
                    $this->numOfErrors++;
                } elseif ($failure['severity'] === 'warning') {
                    $this->reportInternal[$filePath]['warnings']++;
                    $this->numOfWarnings++;
                }
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function countFiles()
    {
        return count($this->report);
    }

    /**
     * {@inheritdoc}
     */
    public function yieldFiles()
    {
        foreach ($this->reportInternal as $filePath => $file) {
            yield $filePath => new FileWrapper($file);
        }
    }

    /**
     * @return string
     */
    public function highestSeverity()
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
    public function numOfErrors()
    {
        return $this->numOfErrors;
    }

    /**
     * {@inheritdoc}
     */
    public function numOfWarnings()
    {
        return $this->numOfWarnings;
    }
}
