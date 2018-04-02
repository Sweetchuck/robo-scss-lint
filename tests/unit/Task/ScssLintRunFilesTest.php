<?php

namespace Sweetchuck\Robo\ScssLint\Tests\Unit\Task;

use Sweetchuck\LintReport\Reporter\VerboseReporter;
use Sweetchuck\Robo\ScssLint\LintReportWrapper\ReportWrapper;
use Sweetchuck\Robo\ScssLint\Task\ScssLintRunFiles as Task;
use Codeception\Test\Unit;
use Codeception\Util\Stub;
use Sweetchuck\Codeception\Module\RoboTaskRunner\DummyOutput;
use Sweetchuck\Codeception\Module\RoboTaskRunner\DummyProcess;
use Robo\Robo;
use Symfony\Component\Console\Output\OutputInterface;

class ScssLintRunFilesTest extends Unit
{
    protected static function getMethod(string $name): \ReflectionMethod
    {
        $class = new \ReflectionClass(Task::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    /**
     * @var \Sweetchuck\Robo\ScssLint\Test\UnitTester
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
        $options = [
            'lintReporters' => [
                'aKey' => 'aValue',
            ],
        ];

        $task = new Task();
        $task
            ->setOptions($options)
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

    public function casesGetCommand(): array
    {
        return [
            'basic' => [
                'bundle exec scss-lint',
                [],
            ],
            'working-directory' => [
                "cd 'my-dir' && bundle exec scss-lint",
                ['workingDirectory' => 'my-dir'],
            ],
            'ruby-executable safe' => [
                "my-ruby bundle exec scss-lint",
                ['rubyExecutable' => 'my-ruby'],
            ],
            'ruby-executable escape' => [
                "my\\\$ruby bundle exec scss-lint",
                ['rubyExecutable' => 'my$ruby'],
            ],
            'env-var-path vector' => [
                "PATH='/a/b/c:/d/e' bundle exec scss-lint",
                [
                    'envVarPath' => [
                        '/a/b/c',
                        '/d/e'
                    ],
                ],
            ],
            'env-var-path assoc' => [
                "PATH='/a/b/c:/d/e' bundle exec scss-lint",
                [
                    'envVarPath' => [
                        '/a/b/c' => true,
                        '/d/e' => true,
                        '/f/g' => false,
                    ],
                ],
            ],
            'env-var-bundle-gem-file' => [
                "BUNDLE_GEMFILE='a/b/Gemfile' bundle exec scss-lint",
                ['envVarBundleGemFile' => 'a/b/Gemfile'],
            ],
            'bundleExecutable-empty' => [
                'scss-lint',
                ['bundleExecutable' => ''],
            ],
            'bundleExecutable-other' => [
                'my-bundle exec scss-lint',
                ['bundleExecutable' => 'my-bundle'],
            ],
            'complex-executable' => [
                "cd 'my-dir' && PATH='/a:/b' BUNDLE_GEMFILE='my-gem-file' my-ruby my-bundle exec scss-lint",
                [
                    'workingDirectory' => 'my-dir',
                    'envVarPath' => ['/a', '/b'],
                    'envVarBundleGemFile' => 'my-gem-file',
                    'rubyExecutable' => 'my-ruby',
                    'bundleExecutable' => 'my-bundle',
                ],
            ],
            'scssLintExecutable-other' => [
                'my-scss-lint',
                [
                    'bundleExecutable' => '',
                    'scssLintExecutable' => 'my-scss-lint',
                ],
            ],
            'format-empty' => [
                'bundle exec scss-lint',
                ['format' => ''],
            ],
            'format-foo' => [
                "bundle exec scss-lint --format='foo'",
                ['format' => 'foo'],
            ],
            'require-string' => [
                "bundle exec scss-lint --require='foo'",
                ['require' => 'foo'],
            ],
            'require-vector' => [
                "bundle exec scss-lint --require='foo' --require='bar' --require='baz'",
                ['require' => ['foo', 'bar', 'baz']],
            ],
            'require-assoc' => [
                "bundle exec scss-lint --require='foo' --require='baz'",
                ['require' => ['foo' => true, 'bar' => false, 'baz' => true]],
                [],
            ],
            'linters-string' => [
                "bundle exec scss-lint --include-linter='foo'",
                ['linters' => 'foo'],
            ],
            'linters-vector' => [
                "bundle exec scss-lint --include-linter='foo,bar,baz'",
                ['linters' => ['foo', 'bar', 'baz']],
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
            ],
            'config-false' => [
                "bundle exec scss-lint",
                ['configFile' => false],
            ],
            'config-string' => [
                "bundle exec scss-lint --config='foo'",
                ['configFile' => 'foo'],
            ],
            'exclude-string' => [
                "bundle exec scss-lint --exclude='foo'",
                ['exclude' => 'foo'],
            ],
            'exclude-vector' => [
                "bundle exec scss-lint --exclude='foo,bar,baz'",
                ['exclude' => ['foo', 'bar', 'baz']],
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
            ],
            'out-false' => [
                "bundle exec scss-lint",
                ['out' => false],
            ],
            'out-foo' => [
                "bundle exec scss-lint --out='foo'",
                ['out' => 'foo'],
            ],
            'color-true' => [
                "bundle exec scss-lint --color",
                ['color' => true],
            ],
            'color-null' => [
                "bundle exec scss-lint",
                ['color' => null],
            ],
            'color-false' => [
                "bundle exec scss-lint --no-color",
                ['color' => false],
            ],
            'paths-vector' => [
                "bundle exec scss-lint -- 'foo' 'bar' 'baz'",
                ['paths' => ['foo', 'bar', 'baz']],
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
            ],
        ];
    }

    /**
     * @dataProvider casesGetCommand
     */
    public function testGetCommand(string $expected, array $options): void
    {
        $task = new Task();
        $task->setOptions($options);
        $this->tester->assertEquals($expected, $task->getCommand());
    }

    public function testExitCodeConstants(): void
    {
        $this->tester->assertEquals(0, Task::EXIT_CODE_OK);
        $this->tester->assertEquals(1, Task::EXIT_CODE_WARNING);
        $this->tester->assertEquals(2, Task::EXIT_CODE_ERROR);
        $this->tester->assertEquals(66, Task::EXIT_CODE_FILE_NOT_FOUND);
        $this->tester->assertEquals(80, Task::EXIT_CODE_GLOB_DID_NOT_MATCH);
    }

    public function casesGetTaskExitCode(): array
    {
        $o = Task::EXIT_CODE_OK;
        $w = Task::EXIT_CODE_WARNING;
        $e = Task::EXIT_CODE_ERROR;
        $n = Task::EXIT_CODE_FILE_NOT_FOUND;
        $g = Task::EXIT_CODE_GLOB_DID_NOT_MATCH;
        $u = 5;

        $variations = [
            [$o, 'n', 1, 1, 0, 0, $o],
            [$n, 'n', 1, 1, 0, 0, $n],
            [$g, 'n', 1, 1, 0, 0, $g],
            [$u, 'n', 1, 1, 0, 0, $u],
            [$o, 'n', 0, 1, 0, 0, $n],
            [$o, 'n', 1, 0, 0, 0, $g],
            [$n, 'w', 1, 1, 0, 0, $n],
            [$u, 'w', 1, 1, 0, 0, $u],
            [$g, 'w', 1, 1, 0, 0, $g],
            [$n, 'e', 1, 1, 0, 0, $n],
            [$u, 'e', 1, 1, 0, 0, $u],
            [$g, 'e', 1, 1, 0, 0, $g],
            [$o, 'n', 0, 0, 0, 0, $o],
            [$o, 'n', 0, 0, 0, 0, $w],
            [$o, 'n', 0, 0, 0, 0, $e],
            [$u, 'n', 0, 0, 0, 0, $u],
            [$o, 'n', 0, 0, 0, 1, $o],
            [$o, 'n', 0, 0, 0, 1, $w],
            [$o, 'n', 0, 0, 0, 1, $e],
            [$u, 'n', 0, 0, 0, 1, $u],
            [$o, 'n', 0, 0, 1, 0, $o],
            [$o, 'n', 0, 0, 1, 0, $w],
            [$o, 'n', 0, 0, 1, 0, $e],
            [$u, 'n', 0, 0, 1, 0, $u],
            [$o, 'n', 0, 0, 1, 1, $o],
            [$o, 'n', 0, 0, 1, 1, $w],
            [$o, 'n', 0, 0, 1, 1, $e],
            [$u, 'n', 0, 0, 1, 1, $u],
            [$o, 'w', 0, 0, 0, 0, $o],
            [$o, 'w', 0, 0, 0, 0, $w],
            [$o, 'w', 0, 0, 0, 0, $e],
            [$u, 'w', 0, 0, 0, 0, $u],
            [$w, 'w', 0, 0, 0, 1, $o],
            [$w, 'w', 0, 0, 0, 1, $w],
            [$w, 'w', 0, 0, 0, 1, $e],
            [$u, 'w', 0, 0, 0, 1, $u],
            [$e, 'w', 0, 0, 1, 0, $o],
            [$e, 'w', 0, 0, 1, 0, $w],
            [$e, 'w', 0, 0, 1, 0, $e],
            [$u, 'w', 0, 0, 1, 0, $u],
            [$e, 'w', 0, 0, 1, 1, $o],
            [$e, 'w', 0, 0, 1, 1, $w],
            [$e, 'w', 0, 0, 1, 1, $e],
            [$u, 'w', 0, 0, 1, 1, $u],
            [$o, 'e', 0, 0, 0, 0, $o],
            [$o, 'e', 0, 0, 0, 0, $w],
            [$o, 'e', 0, 0, 0, 0, $e],
            [$u, 'e', 0, 0, 0, 0, $u],
            [$o, 'e', 0, 0, 0, 1, $o],
            [$o, 'e', 0, 0, 0, 1, $w],
            [$o, 'e', 0, 0, 0, 1, $e],
            [$u, 'e', 0, 0, 0, 1, $u],
            [$e, 'e', 0, 0, 1, 0, $o],
            [$e, 'e', 0, 0, 1, 0, $w],
            [$e, 'e', 0, 0, 1, 0, $e],
            [$u, 'e', 0, 0, 1, 0, $u],
            [$e, 'e', 0, 0, 1, 1, $o],
            [$e, 'e', 0, 0, 1, 1, $w],
            [$e, 'e', 0, 0, 1, 1, $e],
            [$u, 'e', 0, 0, 1, 1, $u],
        ];

        $cases = [];
        foreach ($variations as $variation) {
            $id = implode(', ', array_slice($variation, 1));
            $variation[1] = $this->expandFailOnAbbreviation($variation[1]);
            $cases[$id] = $variation;
        }

        return $cases;
    }

    /**
     * @dataProvider casesGetTaskExitCode
     */
    public function testGetTaskExitCode(
        int $expected,
        string $failOn,
        bool $failOnFileNotFound,
        bool $failOnGlobDidNotMatch,
        int $numOfErrors,
        int $numOfWarnings,
        int $lintExitCode
    ): void {
        /** @var Task $task */
        $task = Stub::construct(
            Task::class,
            [],
            [
                'lintExitCode' => $lintExitCode,
                'reportWrapper' => $this->createReportWrapper($numOfErrors, $numOfWarnings),
            ]
        );
        $task
            ->setFailOn($failOn)
            ->setFailOnFileNotFound($failOnFileNotFound)
            ->setFailOnGlobDidNotMatch($failOnGlobDidNotMatch);

        $this->tester->assertEquals(
            $expected,
            static::getMethod('getTaskExitCode')->invoke($task, $numOfErrors, $numOfWarnings)
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

        $label_pattern = '%d; failOn: %s; E: %d; W: %d; exitCode: %d;';
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
        foreach ($combinations as $c) {
            $i++;
            $report = $reportBase;

            if ($c['e']) {
                $report['a.scss'][] = $messageError;
            }

            if ($c['w']) {
                $report['a.scss'][] = $messageWarning;
            }

            $label = sprintf($label_pattern, $i, $c['f'], $c['e'], $c['w'], $c['c']);
            $cases[$label] = [
                $c['c'],
                [
                    'failOn' => $c['f'],
                    'assetNamePrefix' => ($i % 3 === 0 ? 'my-prefix.' : ''),
                ],
                json_encode($report)
            ];
        }

        return $cases;
    }

    /**
     * This way cannot be tested those cases when the lint process failed.
     *
     * @dataProvider casesRun
     */
    public function testRun(int $exitCode, array $options, string $expectedStdOutput): void
    {
        $container = Robo::createDefaultContainer();
        Robo::setContainer($container);

        $outputConfig = [
            'verbosity' => OutputInterface::VERBOSITY_DEBUG,
            'colors' => false,
        ];
        $mainStdOutput = new DummyOutput($outputConfig);

        $options += [
            'workingDirectory' => 'my-working-dir',
            'format' => 'JSON',
            'failOn' => 'warning',
            'failOnGlobDidNotMatch' => false,
        ];

        /** @var \Sweetchuck\Robo\ScssLint\Task\ScssLintRun $task */
        $task = Stub::construct(
            Task::class,
            [],
            [
                'processClass' => DummyProcess::class,
            ]
        );
        $task->setOptions($options);

        $processIndex = count(DummyProcess::$instances);

        DummyProcess::$prophecy[$processIndex] = [
            'exitCode' => $exitCode,
            'stdOutput' => $expectedStdOutput,
            'stdError' => '',
        ];

        $task->setLogger($container->get('logger'));
        $task->setOutput($mainStdOutput);

        $result = $task->run();

        $this->tester->assertEquals(
            $exitCode,
            $result->getExitCode(),
            'Exit code'
        );

        $assetNamePrefix = $options['assetNamePrefix'] ?? '';

        /** @var \Sweetchuck\LintReport\ReportWrapperInterface $reportWrapper */
        $reportWrapper = $result["{$assetNamePrefix}report"];
        $this->tester->assertEquals(
            json_decode($expectedStdOutput, true),
            $reportWrapper->getReport(),
            'Output equals with jar'
        );

        $this->tester->assertContains(
            $expectedStdOutput,
            $mainStdOutput->output,
            'Output contains'
        );
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
            [],
            [
                'container' => $container,
            ]
        );
        $task->setOptions($options);

        $task->setLogger($container->get('logger'));

        $result = $task->run();

        $this->assertEquals(3, $result->getExitCode());
        $this->assertEquals(
            'Extra lint reporters can be used only if the output format is "JSON".',
            $result->getMessage()
        );
    }

    protected function createReportWrapper(int $numOfErrors, int $numOfWarnings): ReportWrapper
    {
        return new ReportWrapper($this->createReport($numOfErrors, $numOfWarnings));
    }

    protected function createReport(int $numOfErrors, int $numOfWarnings): array
    {
        $report = [
            'a.scss' => [],
        ];
        foreach (['error' => $numOfErrors, 'warning' => $numOfWarnings] as $severity => $numOfIssue) {
            for ($i = 0; $i < $numOfIssue; $i++) {
                $report['a.scss'][] = [
                    'severity' => $severity,
                ];
            }
        }

        return $report;
    }

    protected function expandFailOnAbbreviation(string $abbreviation): string
    {
        switch ($abbreviation) {
            case 'n':
                return 'never';

            case 'w':
                return 'warning';

            case 'e':
                return 'error';
        }

        throw new \InvalidArgumentException();
    }
}
