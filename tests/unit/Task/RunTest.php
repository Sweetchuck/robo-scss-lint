<?php

use Cheppers\LintReport\Reporter\VerboseReporter;
use Cheppers\Robo\ScssLint\Task\Run as Task;
use Codeception\Util\Stub;
use Robo\Robo;

/**
 * Class TaskScssLintRunTest.
 */
// @codingStandardsIgnoreStart
class TaskScssLintRunTest extends \Codeception\Test\Unit
{
    // @codingStandardsIgnoreEnd

    use \Cheppers\Robo\ScssLint\Task\LoadTasks;
    use \League\Container\ContainerAwareTrait;
    use \Robo\TaskAccessor;
    use \Robo\Common\BuilderAwareTrait;

    /**
     * @param $name
     *
     * @return \ReflectionMethod
     */
    protected static function getMethod($name)
    {
        $class = new ReflectionClass(Task::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    /**
     * @var \League\Container\Container
     */
    protected $container = null;

    // @codingStandardsIgnoreStart
    protected function _before()
    {
        // @codingStandardsIgnoreEnd
        $this->container = new \League\Container\Container();
        Robo::setContainer($this->container);
        Robo::configureContainer($this->container);
    }

    public function testGetSetLintReporters()
    {
        $task = new Task([
            'lintReporters' => [
                'aKey' => 'aValue',
            ],
        ]);

        $task
            ->addLintReporter('bKey', 'bValue')
            ->addLintReporter('cKey', 'cValue')
            ->removeLintReporter('bKey');

        $this->assertEquals(
            [
                'aKey' => 'aValue',
                'cKey' => 'cValue',
            ],
            $task->getLintReporters()
        );
    }

    /**
     * @return array
     */
    public function casesBuildCommand()
    {
        return [
            'basic' => [
                'bundle exec scss-lint',
                [],
                [],
            ],
            'format-empty' => [
                'bundle exec scss-lint',
                ['format' => ''],
                [],
            ],
            'format-foo' => [
                "bundle exec scss-lint --format='foo'",
                ['format' => 'foo'],
                [],
            ],
            'require-string' => [
                "bundle exec scss-lint --require='foo'",
                ['requires' => 'foo'],
                [],
            ],
            'require-vector' => [
                "bundle exec scss-lint --require='foo' --require='bar' --require='baz'",
                ['requires' => ['foo', 'bar', 'baz']],
                [],
            ],
            'require-assoc' => [
                "bundle exec scss-lint --require='foo' --require='baz'",
                ['requires' => ['foo' => true, 'bar' => false, 'baz' => true]],
                [],
            ],
            'linters-string' => [
                "bundle exec scss-lint --include-linter='foo'",
                ['linters' => 'foo'],
                [],
            ],
            'linters-vector' => [
                "bundle exec scss-lint --include-linter='foo,bar,baz'",
                ['linters' => ['foo', 'bar', 'baz']],
                [],
            ],
            'linters-assoc' => [
                "bundle exec scss-lint --include-linter='a,d' --exclude-linter='c,e'",
                [
                    'linters' => [
                        'a' => true,
                        'b' => null,
                        'c' => false,
                        'd' => true,
                        'e' => false,
                    ]
                ],
                [],
            ],
            'config-false' => [
                "bundle exec scss-lint",
                ['configFile' => false],
                [],
            ],
            'config-string' => [
                "bundle exec scss-lint --config='foo'",
                ['configFile' => 'foo'],
                [],
            ],
            'exclude-string' => [
                "bundle exec scss-lint --exclude='foo'",
                ['exclude' => 'foo'],
                [],
            ],
            'exclude-vector' => [
                "bundle exec scss-lint --exclude='foo,bar,baz'",
                ['exclude' => ['foo', 'bar', 'baz']],
                [],
            ],
            'exclude-assoc' => [
                "bundle exec scss-lint --exclude='a,d'",
                [
                    'exclude' => [
                        'a' => true,
                        'b' => null,
                        'c' => false,
                        'd' => true,
                        'e' => false,
                    ]
                ],
                [],
            ],
            'out-false' => [
                "bundle exec scss-lint",
                ['out' => false],
                [],
            ],
            'out-foo' => [
                "bundle exec scss-lint --out='foo'",
                ['out' => 'foo'],
                [],
            ],
            'color-true' => [
                "bundle exec scss-lint --color",
                ['color' => true],
                [],
            ],
            'color-null' => [
                "bundle exec scss-lint",
                ['color' => null],
                [],
            ],
            'color-false' => [
                "bundle exec scss-lint --no-color",
                ['color' => false],
                [],
            ],
            'paths-vector' => [
                "bundle exec scss-lint -- 'foo' 'bar' 'baz'",
                ['paths' => ['foo', 'bar', 'baz']],
                [],
            ],
            'paths-assoc' => [
                "bundle exec scss-lint -- 'a' 'd'",
                [
                    'paths' => [
                        'a' => true,
                        'b' => null,
                        'c' => false,
                        'd' => true,
                        'e' => false,
                    ]
                ],
                [],
            ],
        ];
    }

    /**
     * @dataProvider casesBuildCommand
     *
     * @param string $expected
     * @param array $options
     * @param array $paths
     */
    public function testBuildCommand($expected, array $options, array $paths)
    {
        $task = new Task($options, $paths);
        static::assertEquals($expected, $task->buildCommand());
    }

    public function testExitCodeConstants()
    {
        static::assertEquals(0, Task::EXIT_CODE_OK);
        static::assertEquals(1, Task::EXIT_CODE_WARNING);
        static::assertEquals(2, Task::EXIT_CODE_ERROR);
        static::assertEquals(80, Task::EXIT_CODE_NO_FILES);
    }

    /**
     * @return array
     */
    public function casesGetTaskExitCode()
    {
        $old = [
            'never-ok' => [
                Task::EXIT_CODE_OK,
                [
                    'failOn' => 'never',
                    'failOnNoFiles' => true,
                ],
                Task::EXIT_CODE_OK,
            ],
            'never-warning' => [
                Task::EXIT_CODE_OK,
                [
                    'failOn' => 'never',
                    'failOnNoFiles' => true,
                ],
                Task::EXIT_CODE_WARNING,
            ],
            'never-error' => [
                Task::EXIT_CODE_OK,
                [
                    'failOn' => 'never',
                    'failOnNoFiles' => true,
                ],
                Task::EXIT_CODE_ERROR,
            ],
            'never-no-files-false' => [
                Task::EXIT_CODE_OK,
                [
                    'failOn' => 'never',
                    'failOnNoFiles' => false,
                ],
                Task::EXIT_CODE_NO_FILES,
            ],
            'never-no-files-true' => [
                Task::EXIT_CODE_NO_FILES,
                [
                    'failOn' => 'never',
                    'failOnNoFiles' => true,
                ],
                Task::EXIT_CODE_NO_FILES,
            ],
            'warning-ok' => [
                Task::EXIT_CODE_OK,
                [
                    'failOn' => 'warning',
                    'failOnNoFiles' => false,
                ],
                Task::EXIT_CODE_OK,
            ],
            'warning-warning' => [
                Task::EXIT_CODE_WARNING,
                [
                    'failOn' => 'warning',
                    'failOnNoFiles' => false,
                ],
                Task::EXIT_CODE_WARNING,
            ],
            'warning-error' => [
                Task::EXIT_CODE_ERROR,
                [
                    'failOn' => 'warning',
                    'failOnNoFiles' => false,
                ],
                Task::EXIT_CODE_ERROR,
            ],
            'warning-no-files-false' => [
                Task::EXIT_CODE_OK,
                [
                    'failOn' => 'warning',
                    'failOnNoFiles' => false,
                ],
                Task::EXIT_CODE_NO_FILES,
            ],
            'warning-no-files-true' => [
                Task::EXIT_CODE_NO_FILES,
                [
                    'failOn' => 'warning',
                    'failOnNoFiles' => true,
                ],
                Task::EXIT_CODE_NO_FILES,
            ],
            'error-ok' => [
                Task::EXIT_CODE_OK,
                [
                    'failOn' => 'error',
                    'failOnNoFiles' => false,
                ],
                Task::EXIT_CODE_OK,
            ],
            'error-warning' => [
                Task::EXIT_CODE_OK,
                [
                    'failOn' => 'error',
                    'failOnNoFiles' => false,
                ],
                Task::EXIT_CODE_WARNING,
            ],
            'error-error' => [
                Task::EXIT_CODE_ERROR,
                [
                    'failOn' => 'error',
                    'failOnNoFiles' => false,
                ],
                Task::EXIT_CODE_ERROR,
            ],
            'error-no-files-false' => [
                Task::EXIT_CODE_OK,
                [
                    'failOn' => 'error',
                    'failOnNoFiles' => false,
                ],
                Task::EXIT_CODE_NO_FILES,
            ],
            'error-no-files-true' => [
                Task::EXIT_CODE_NO_FILES,
                [
                    'failOn' => 'error',
                    'failOnNoFiles' => true,
                ],
                Task::EXIT_CODE_NO_FILES,
            ],
        ];

        $o = Task::EXIT_CODE_OK;
        $w = Task::EXIT_CODE_WARNING;
        $e = Task::EXIT_CODE_ERROR;
        $n = Task::EXIT_CODE_NO_FILES;
        $u = 5;

        return [
            'never-n00n' => [$n, 'never', 1, 0, 0, $n],
            'never-n005' => [$u, 'never', 1, 0, 0, $u],

            'warning-n00n' => [$n, 'warning', 1, 0, 0, $n],
            'warning-n005' => [$u, 'warning', 1, 0, 0, $u],

            'error-n00n' => [$n, 'error', 1, 0, 0, $n],
            'error-n005' => [$u, 'error', 1, 0, 0, $u],

            'never-000' => [$o, 'never', 0, 0, 0, 0],
            'never-001' => [$o, 'never', 0, 0, 0, 1],
            'never-002' => [$o, 'never', 0, 0, 0, 2],
            'never-005' => [$u, 'never', 0, 0, 0, 5],

            'never-010' => [$o, 'never', 0, 0, 1, 0],
            'never-011' => [$o, 'never', 0, 0, 1, 1],
            'never-012' => [$o, 'never', 0, 0, 1, 2],
            'never-015' => [$u, 'never', 0, 0, 1, 5],

            'never-100' => [$o, 'never', 0, 1, 0, 0],
            'never-101' => [$o, 'never', 0, 1, 0, 1],
            'never-102' => [$o, 'never', 0, 1, 0, 2],
            'never-105' => [$u, 'never', 0, 1, 0, 5],

            'never-110' => [$o, 'never', 0, 1, 1, 0],
            'never-111' => [$o, 'never', 0, 1, 1, 1],
            'never-112' => [$o, 'never', 0, 1, 1, 2],
            'never-115' => [$u, 'never', 0, 1, 1, 5],

            'warning-000' => [$o, 'warning', 0, 0, 0, 0],
            'warning-001' => [$o, 'warning', 0, 0, 0, 1],
            'warning-002' => [$o, 'warning', 0, 0, 0, 2],
            'warning-005' => [$u, 'warning', 0, 0, 0, 5],

            'warning-010' => [$w, 'warning', 0, 0, 1, 0],
            'warning-011' => [$w, 'warning', 0, 0, 1, 1],
            'warning-012' => [$w, 'warning', 0, 0, 1, 2],
            'warning-015' => [$u, 'warning', 0, 0, 1, 5],

            'warning-100' => [$e, 'warning', 0, 1, 0, 0],
            'warning-101' => [$e, 'warning', 0, 1, 0, 1],
            'warning-102' => [$e, 'warning', 0, 1, 0, 2],
            'warning-105' => [$u, 'warning', 0, 1, 0, 5],

            'warning-110' => [$e, 'warning', 0, 1, 1, 0],
            'warning-111' => [$e, 'warning', 0, 1, 1, 1],
            'warning-112' => [$e, 'warning', 0, 1, 1, 2],
            'warning-115' => [$u, 'warning', 0, 1, 1, 5],

            'error-000' => [$o, 'error', 0, 0, 0, 0],
            'error-001' => [$o, 'error', 0, 0, 0, 1],
            'error-002' => [$o, 'error', 0, 0, 0, 2],
            'error-005' => [$u, 'error', 0, 0, 0, 5],

            'error-010' => [$o, 'error', 0, 0, 1, 0],
            'error-011' => [$o, 'error', 0, 0, 1, 1],
            'error-012' => [$o, 'error', 0, 0, 1, 2],
            'error-015' => [$u, 'error', 0, 0, 1, 5],

            'error-100' => [$e, 'error', 0, 1, 0, 0],
            'error-101' => [$e, 'error', 0, 1, 0, 1],
            'error-102' => [$e, 'error', 0, 1, 0, 2],
            'error-105' => [$u, 'error', 0, 1, 0, 5],

            'error-110' => [$e, 'error', 0, 1, 1, 0],
            'error-111' => [$e, 'error', 0, 1, 1, 1],
            'error-112' => [$e, 'error', 0, 1, 1, 2],
            'error-115' => [$u, 'error', 0, 1, 1, 5],
        ];
    }

    /**
     * @dataProvider casesGetTaskExitCode
     *
     * @param int $expected
     * @param string $failOn
     * @param bool $failOnNoFiles
     * @param int $numOfErrors
     * @param int $numOfWarnings
     * @param int $exitCode
     *
     * @internal param array $options
     */
    public function testGetTaskExitCode($expected, $failOn, $failOnNoFiles, $numOfErrors, $numOfWarnings, $exitCode)
    {
        /** @var Task $eslint */
        $task = Stub::construct(
            Task::class,
            [['failOn' => $failOn, 'failOnNoFiles' => $failOnNoFiles]],
            ['exitCode' => $exitCode]
        );

        static::assertEquals(
            $expected,
            static::getMethod('getTaskExitCode')->invokeArgs($task, [$numOfErrors, $numOfWarnings])
        );
    }

    /**
     * @return array
     */
    public function casesRun()
    {
        return [
            'withoutJar - success' => [
                0,
                [],
                false,
            ],
            'withoutJar - warning' => [
                1,
                [
                    'a.scss' => [
                        [
                            'severity' => 'warning',
                        ],
                    ],
                ],
                false,
            ],
            'withoutJar - error' => [
                2,
                [
                    'a.scss' => [
                        [
                            'severity' => 'error',
                        ],
                    ]
                ],
                false,
            ],
            'withJar - success' => [
                0,
                [],
                true,
            ],
            'withJar - warning' => [
                1,
                [
                    'a.scss' => [
                        [
                            'severity' => 'warning',
                        ],
                    ],
                ],
                true,
            ],
            'withJar - error' => [
                2,
                [
                    'a.scss' => [
                        [
                            'severity' => 'error',
                        ],
                    ],
                ],
                true,
            ],
        ];
    }

    /**
     * This way cannot be tested those cases when the lint process failed.
     *
     * @dataProvider casesRun
     *
     * @param int $expectedExitCode
     * @param array $expectedReport
     * @param bool $withJar
     */
    public function testRun($expectedExitCode, array $expectedReport, $withJar)
    {
        $options = [
            'workingDirectory' => 'my-working-dir',
            'assetJarMapping' => ['report' => ['scssLintRun', 'report']],
            'format' => 'JSON',
            'failOn' => 'warning',
            'failOnNoFiles' => false,
        ];

        /** @var Task $task */
        $task = Stub::construct(
            Task::class,
            [$options, []],
            [
                'processClass' => \Helper\Dummy\Process::class,
            ]
        );

        $output = new \Helper\Dummy\Output();
        \Helper\Dummy\Process::$exitCode = $expectedExitCode;
        \Helper\Dummy\Process::$stdOutput = json_encode($expectedReport);

        $task->setLogger($this->container->get('logger'));
        $task->setOutput($output);
        $assetJar = null;
        if ($withJar) {
            $assetJar = new \Cheppers\AssetJar\AssetJar();
            $task->setAssetJar($assetJar);
        }

        $result = $task->run();

        static::assertEquals($expectedExitCode, $result->getExitCode(), 'Exit code');
        static::assertEquals(
            $options['workingDirectory'],
            \Helper\Dummy\Process::$instance->getWorkingDirectory(),
            'Working directory'
        );

        if ($withJar) {
            /** @var \Cheppers\Robo\ScssLint\LintReportWrapper\ReportWrapper $reportWrapper */
            $reportWrapper = $assetJar->getValue(['scssLintRun', 'report']);
            static::assertEquals(
                $expectedReport,
                $reportWrapper->getReport(),
                'Output equals with jar'
            );
        } else {
            static::assertEquals(
                $expectedReport,
                json_decode($output->output, true),
                'Output equals without jar'
            );
        }
    }

    public function testRunFailed()
    {
        $exitCode = 2;
        $report = [
            'a.scss' => [
                [
                    'line' => 1,
                    'column' => 2,
                    'length' => 3,
                    'severity' => 'error',
                    'reason' => 'r1',
                ],
            ],
        ];
        $reportJson = json_encode($report);
        $options = [
            'workingDirectory' => 'my-working-dir',
            'assetJarMapping' => ['report' => ['ScssLintRun', 'report']],
            'format' => 'JSON',
            'failOn' => 'warning',
        ];

        /** @var Task $task */
        $task = Stub::construct(
            Task::class,
            [$options, []],
            [
                'processClass' => \Helper\Dummy\Process::class,
            ]
        );

        \Helper\Dummy\Process::$exitCode = $exitCode;
        \Helper\Dummy\Process::$stdOutput = $reportJson;

        $task->setConfig(Robo::config());
        $task->setLogger($this->container->get('logger'));
        $assetJar = new \Cheppers\AssetJar\AssetJar();
        $task->setAssetJar($assetJar);

        $result = $task->run();

        static::assertEquals($exitCode, $result->getExitCode());
        static::assertEquals(
            $options['workingDirectory'],
            \Helper\Dummy\Process::$instance->getWorkingDirectory()
        );

        /** @var \Cheppers\Robo\ScssLint\LintReportWrapper\ReportWrapper $reportWrapper */
        $reportWrapper = $assetJar->getValue(['ScssLintRun', 'report']);
        static::assertEquals($report, $reportWrapper->getReport());
    }

    public function testRunNativeAndExtraReporterConflict()
    {
        $options = [
            'format' => 'stylish',
            'lintReporters' => [
                'aKey' => new VerboseReporter(),
            ],
        ];

        /** @var Task $task */
        $task = Stub::construct(
            Task::class,
            [$options, []],
            [
                'container' => $this->getContainer(),
            ]
        );

        $task->setConfig(Robo::config());
        $task->setLogger($this->container->get('logger'));
        $assetJar = new \Cheppers\AssetJar\AssetJar();
        $task->setAssetJar($assetJar);

        $result = $task->run();

        $this->assertEquals(3, $result->getExitCode());
        $this->assertEquals(
            'Extra lint reporters can be used only if the output format is "json".',
            $result->getMessage()
        );
    }
}
