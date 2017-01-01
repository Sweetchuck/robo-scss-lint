<?php

namespace Cheppers\Robo\ScssLint\Test\Unit\Task;

use Cheppers\AssetJar\AssetJar;
use Cheppers\LintReport\Reporter\VerboseReporter;
use Cheppers\Robo\ScssLint\Task\Run as Task;
use Codeception\Test\Unit;
use Codeception\Util\Stub;
use Helper\Dummy\Output as DummyOutput;
use Helper\Dummy\Process as DummyProcess;
use Robo\Robo;

class TaskScssLintRunTest extends Unit
{
    protected static function getMethod(string $name): \ReflectionMethod
    {
        $class = new \ReflectionClass(Task::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        DummyProcess::reset();
    }

    public function testGetSetLintReporters(): void
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

        $this->tester->assertEquals(
            [
                'aKey' => 'aValue',
                'cKey' => 'cValue',
            ],
            $task->getLintReporters()
        );
    }

    public function casesBuildCommand(): array
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
     */
    public function testBuildCommand(string $expected, array $options, array $paths): void
    {
        $task = new Task($options, $paths);
        $this->tester->assertEquals($expected, $task->buildCommand());
    }

    public function testExitCodeConstants(): void
    {
        $this->tester->assertEquals(0, Task::EXIT_CODE_OK);
        $this->tester->assertEquals(1, Task::EXIT_CODE_WARNING);
        $this->tester->assertEquals(2, Task::EXIT_CODE_ERROR);
        $this->tester->assertEquals(80, Task::EXIT_CODE_NO_FILES);
    }

    public function casesGetTaskExitCode(): array
    {
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
     */
    public function testGetTaskExitCode(
        int $expected,
        string $failOn,
        bool $failOnNoFiles,
        int $numOfErrors,
        int $numOfWarnings,
        int $exitCode
    ): void {
        /** @var Task $task */
        $task = Stub::construct(
            Task::class,
            [['failOn' => $failOn, 'failOnNoFiles' => $failOnNoFiles]],
            ['exitCode' => $exitCode]
        );

        $this->tester->assertEquals(
            $expected,
            static::getMethod('getTaskExitCode')->invokeArgs($task, [$numOfErrors, $numOfWarnings])
        );
    }

    public function casesRun(): array
    {
        $reportBase = [];

        $messageWarning = [
            'line' => 1,
            'column' => 2,
            'length' => 3,
            'severity' => 'warning',
            'reason' => 'R1',
            'linter' => 'S1',
        ];

        $messageError = [
            'line' => 3,
            'column' => 4,
            'length' => 5,
            'severity' => 'error',
            'reason' => 'R2',
            'linter' => 'S2',
        ];

        $label_pattern = '%d; failOn: %s; E: %d; W: %d; exitCode: %d; withJar: %s;';
        $cases = [];

        $combinations = [
            ['e' => true, 'w' => true, 'f' => 'never', 'c' => 0],
            ['e' => true, 'w' => false, 'f' => 'never', 'c' => 0],
            ['e' => false, 'w' => true, 'f' => 'never', 'c' => 0],
            ['e' => false, 'w' => false, 'f' => 'never', 'c' => 0],

            ['e' => true, 'w' => true, 'f' => 'warning', 'c' => 2],
            ['e' => true, 'w' => false, 'f' => 'warning', 'c' => 2],
            ['e' => false, 'w' => true, 'f' => 'warning', 'c' => 1],
            ['e' => false, 'w' => false, 'f' => 'warning', 'c' => 0],

            ['e' => true, 'w' => true, 'f' => 'error', 'c' => 2],
            ['e' => true, 'w' => false, 'f' => 'error', 'c' => 2],
            ['e' => false, 'w' => true, 'f' => 'error', 'c' => 0],
            ['e' => false, 'w' => false, 'f' => 'error', 'c' => 0],
        ];

        $i = 0;
        foreach ([true, false] as $withJar) {
            $withJarStr = $withJar ? 'true' : 'false';
            foreach ($combinations as $c) {
                $i++;
                $report = $reportBase;

                if ($c['e']) {
                    $report['a.scss'][] = $messageError;
                }

                if ($c['w']) {
                    $report['a.scss'][] = $messageWarning;
                }

                $label = sprintf($label_pattern, $i, $c['f'], $c['e'], $c['w'], $c['c'], $withJarStr);
                $cases[$label] = [
                    $c['c'],
                    [
                        'failOn' => $c['f'],
                    ],
                    $withJar,
                    json_encode($report)
                ];
            }
        }

        return $cases;
    }

    /**
     * This way cannot be tested those cases when the lint process failed.
     *
     * @dataProvider casesRun
     */
    public function testRun(int $exitCode, array $options, bool $withJar, string $expectedStdOutput): void
    {
        $container = Robo::createDefaultContainer();
        Robo::setContainer($container);

        $mainStdOutput = new DummyOutput();

        $options += [
            'workingDirectory' => 'my-working-dir',
            'assetJarMapping' => ['report' => ['scssLintRun', 'report']],
            'format' => 'JSON',
            'failOn' => 'warning',
            'failOnNoFiles' => false,
        ];

        /** @var \Cheppers\Robo\ScssLint\Task\Run $task */
        $task = Stub::construct(
            Task::class,
            [$options, []],
            [
                'processClass' => DummyProcess::class,
            ]
        );

        $processIndex = count(DummyProcess::$instances);

        DummyProcess::$prophecy[$processIndex] = [
            'exitCode' => $exitCode,
            'stdOutput' => $expectedStdOutput,
        ];

        $task->setLogger($container->get('logger'));
        $task->setOutput($mainStdOutput);

        $assetJar = null;
        if ($withJar) {
            $assetJar = new AssetJar();
            $task->setAssetJar($assetJar);
        }

        $result = $task->run();

        $this->tester->assertEquals(
            $exitCode,
            $result->getExitCode(),
            'Exit code'
        );

        $this->tester->assertEquals(
            $options['workingDirectory'],
            DummyProcess::$instances[$processIndex]->getWorkingDirectory(),
            'Working directory'
        );

        if ($withJar) {
            /** @var \Cheppers\Robo\ScssLint\LintReportWrapper\ReportWrapper $reportWrapper */
            $reportWrapper = $assetJar->getValue(['scssLintRun', 'report']);
            $this->tester->assertEquals(
                json_decode($expectedStdOutput, true),
                $reportWrapper->getReport(),
                'Output equals with jar'
            );
        } else {
            $this->tester->assertContains(
                $expectedStdOutput,
                $mainStdOutput->output,
                'Output contains'
            );
        }
    }

    public function testRunNativeAndExtraReporterConflict(): void
    {
        $container = Robo::createDefaultContainer();
        Robo::setContainer($container);

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
                'container' => $container,
            ]
        );

        $task->setLogger($container->get('logger'));
        $assetJar = new AssetJar();
        $task->setAssetJar($assetJar);

        $result = $task->run();

        $this->assertEquals(3, $result->getExitCode());
        $this->assertEquals(
            'Extra lint reporters can be used only if the output format is "json".',
            $result->getMessage()
        );
    }
}
