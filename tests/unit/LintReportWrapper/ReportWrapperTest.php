<?php

// @codingStandardsIgnoreStart
use Cheppers\Robo\ScssLint\LintReportWrapper\ReportWrapper;

/**
 * Class ReportWrapperTest.
 */
class ReportWrapperTest extends \Codeception\Test\Unit
{
    // @codingStandardsIgnoreEnd

    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @return array
     */
    public function casesReports()
    {
        return [
            'ok:no-files' => [
                'expected' => [
                    'countFiles' => 0,
                    'numOfErrors' => 0,
                    'numOfWarnings' => 0,
                    'highestSeverity' => 'ok',
                ],
                'report' => [],
                'filesStats' => [],
            ],
            'ok:one-file' => [
                'expected' => [
                    'countFiles' => 1,
                    'numOfErrors' => 0,
                    'numOfWarnings' => 0,
                    'highestSeverity' => 'ok',
                ],
                'report' => [
                    'a.scss' => [],
                ],
                'filesStats' => [
                    'a.scss' => [
                        'numOfErrors' => 0,
                        'numOfWarnings' => 0,
                        'highestSeverity' => 'ok',
                        'stats' => [
                            'severity' => 'ok',
                            'has' => [
                                'ok' => false,
                                'warning' => false,
                                'error' => false,
                            ],
                            'source' => [],
                        ],
                    ],
                ],
            ],
            'warning:one-file' => [
                'expected' => [
                    'countFiles' => 1,
                    'numOfErrors' => 0,
                    'numOfWarnings' => 1,
                    'highestSeverity' => 'warning',
                ],
                'report' => [
                    'a.scss' => [
                        [
                            'line' => 1,
                            'column' => 2,
                            'length' => 3,
                            'severity' => 'warning',
                            'reason' => 'r1',
                            'linter' => 'l1',
                        ],
                    ],
                ],
                'filesStats' => [
                    'a.scss' => [
                        'numOfErrors' => 0,
                        'numOfWarnings' => 1,
                        'highestSeverity' => 'warning',
                        'stats' => [
                            'severity' => 'warning',
                            'has' => [
                                'ok' => false,
                                'warning' => true,
                                'error' => false,
                            ],
                            'source' => [
                                'l1' => [
                                    'severity' => 'warning',
                                    'count' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'error:one-file' => [
                'expected' => [
                    'countFiles' => 1,
                    'numOfErrors' => 1,
                    'numOfWarnings' => 2,
                    'highestSeverity' => 'error',
                ],
                'report' => [
                    'a.scss' => [
                        1 => [
                            'line' => 8,
                            'column' => 2,
                            'length' => 3,
                            'severity' => 'error',
                            'reason' => 'r1',
                            'linter' => 'l1',
                        ],
                        0 => [
                            'line' => 1,
                            'column' => 4,
                            'length' => 5,
                            'severity' => 'warning',
                            'reason' => 'r2',
                            'linter' => 'l2',
                        ],
                        2 => [
                            'line' => 9,
                            'column' => 10,
                            'length' => 11,
                            'severity' => 'warning',
                            'reason' => 'r3',
                            'linter' => 'l2',
                        ],
                    ],
                ],
                'filesStats' => [
                    'a.scss' => [
                        'numOfErrors' => 1,
                        'numOfWarnings' => 2,
                        'highestSeverity' => 'error',
                        'stats' => [
                            'severity' => 'error',
                            'has' => [
                                'ok' => false,
                                'warning' => true,
                                'error' => true,
                            ],
                            'source' => [
                                'l2' => [
                                    'severity' => 'warning',
                                    'count' => 2,
                                ],
                                'l1' => [
                                    'severity' => 'error',
                                    'count' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array $expected
     * @param array $report
     * @param array $filesStats
     *
     * @dataProvider casesReports
     */
    public function testAll(array $expected, array $report, array $filesStats)
    {
        $rw = new ReportWrapper($report);

        $this->tester->assertEquals($expected['countFiles'], $rw->countFiles());
        $this->tester->assertEquals($expected['numOfErrors'], $rw->numOfErrors());
        $this->tester->assertEquals($expected['numOfWarnings'], $rw->numOfWarnings());
        $this->tester->assertEquals($expected['highestSeverity'], $rw->highestSeverity());

        /**
         * @var string $filePath
         * @var \Cheppers\Robo\ScssLint\LintReportWrapper\FileWrapper $fw
         */
        foreach ($rw->yieldFiles() as $filePath => $fw) {
            $fileStats = $filesStats[$filePath];
            $this->tester->assertEquals($filePath, $fw->filePath());
            $this->tester->assertEquals($fileStats['numOfErrors'], $fw->numOfErrors());
            $this->tester->assertEquals($fileStats['numOfWarnings'], $fw->numOfWarnings());
            $this->tester->assertEquals($fileStats['highestSeverity'], $fw->highestSeverity());
            $this->tester->assertEquals($fileStats['stats'], $fw->stats());

            /**
             * @var int $i
             * @var \Cheppers\LintReport\FailureWrapperInterface $failureWrapper
             */
            foreach ($fw->yieldFailures() as $i => $failureWrapper) {
                $failure = $report[$filePath][$i];
                $this->tester->assertEquals($failure['severity'], $failureWrapper->severity());
                $this->tester->assertEquals($failure['linter'], $failureWrapper->source());
                $this->tester->assertEquals($failure['line'], $failureWrapper->line());
                $this->tester->assertEquals($failure['column'], $failureWrapper->column());
                $this->tester->assertEquals($failure['reason'], $failureWrapper->message());
            }
        }
    }
}
