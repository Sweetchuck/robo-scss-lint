<?php

namespace Sweetchuck\Robo\ScssLint\Tests\Unit\Task;

use Sweetchuck\Robo\ScssLint\Task\ScssLintRunInput as Task;
use Codeception\Test\Unit;
use Codeception\Util\Stub;
use Sweetchuck\Codeception\Module\RoboTaskRunner\DummyOutput;
use Sweetchuck\Codeception\Module\RoboTaskRunner\DummyProcess;
use Robo\Robo;
use Symfony\Component\Console\Output\OutputInterface;

class ScssLintRunInputTest extends Unit
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

    public function testGetSetOptions()
    {
        $options = [
            'stdinFilePath' => 'abc',
        ];

        $task = new Task();
        $task->setOptions($options);

        $this->tester->assertEquals($options['stdinFilePath'], $task->getStdinFilePath());
    }

    public function casesGetCommand(): array
    {
        return [
            'with content; without workingDirectory' => [
                "echo -n 'content-01' | bundle exec scss-lint --stdin-file-path='a.scss'",
                [
                    'stdinFilePath' => 'a.scss',
                ],
                [
                    'fileName' => 'a.scss',
                    'content' => 'content-01',
                    'command' => 'git show :a.scss',
                ],
            ],
            'with content; with workingDirectory' => [
                "cd 'my-dir' && echo -n 'content-01' | bundle exec scss-lint --stdin-file-path='a.scss'",
                [
                    'workingDirectory' => 'my-dir',
                    'stdinFilePath' => 'a.scss',
                ],
                [
                    'fileName' => 'a.scss',
                    'content' => 'content-01',
                    'command' => 'git show :a.scss',
                ],
            ],
            'without content; without workingDirectory' => [
                "git show :a.scss | bundle exec scss-lint --stdin-file-path='a.scss'",
                [
                    'stdinPath' => 'a.scss',
                ],
                [
                    'fileName' => 'a.scss',
                    'content' => null,
                    'command' => "git show :a.scss",
                ],
            ],
            'without content; with workingDirectory' => [
                "cd 'my-dir' && git show :a.scss | bundle exec scss-lint --stdin-file-path='a.scss'",
                [
                    'workingDirectory' => 'my-dir',
                    'stdinPath' => 'a.scss',
                ],
                [
                    'fileName' => 'a.scss',
                    'content' => null,
                    'command' => "git show :a.scss",
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesGetCommand
     */
    public function testGetCommand(string $expected, array $options, array $currentFile): void
    {
        /** @var \Sweetchuck\Robo\ScssLint\Task\ScssLintRunInput $task */
        $task = Stub::construct(
            Task::class,
            [],
            [
                'currentFile' => $currentFile,
            ]
        );

        $task->setOptions($options);

        $this->tester->assertEquals($expected, $task->getCommand());
    }

    public function casesGetJarValueOrLocal(): array
    {
        return [
            'without jar' => [
                ['a.scss', 'b.scss'],
                'paths',
                ['paths' => ['a.scss', 'b.scss']],
                [],
            ],
            'with jar' => [
                ['c.scss', 'd.scss'],
                'paths',
                [
                    'paths' => ['a.scss', 'b.scss'],
                ],
                [
                    'l1' => [
                        'l2' => ['c.scss', 'd.scss'],
                    ],
                ],
            ],
            'non-exists' => [
                null,
                'non-exists',
                [
                    'paths' => ['a.scss', 'b.scss'],
                ],
                [
                    'l1' => [
                        'l2' => ['c.scss', 'd.scss'],
                    ],
                ],
            ],
        ];
    }

    public function casesRun(): array
    {
        $reports = [
            'w1' => [
                'w1.scss' => [
                    [
                        'line' => 3,
                        'column' => 1,
                        'length' => 2,
                        'severity' => 'warning',
                        'reason' => 'Dummy error message',
                        'linter' => 'SpaceAfterPropertyColon',
                    ],
                ],
            ],
            'w2' => [
                'w2.scss' => [
                    [
                        'line' => 3,
                        'column' => 1,
                        'length' => 2,
                        'severity' => 'warning',
                        'reason' => 'Dummy error message',
                        'linter' => 'SpaceAfterPropertyColon',
                    ],
                ],
            ],
            'e1' => [
                'e1.scss' => [
                    [
                        'line' => 3,
                        'column' => 1,
                        'length' => 2,
                        'severity' => 'error',
                        'reason' => 'Dummy error message',
                        'linter' => 'Indentation',
                    ],
                ],
            ],
        ];

        return [
            'empty' => [
                [
                    'exitCode' => 0,
                    'report' => null,
                    'files' => [],
                ],
                [
                    'format' => 'JSON',
                    'failOn' => 'warning',
                ],
                [],
            ],
            'w0 never' => [
                [
                    'exitCode' => 0,
                    'report' => $reports['w1'] + $reports['w2'],
                ],
                [
                    'format' => 'JSON',
                    'failOn' => 'never',
                    'paths' => [
                        'w1.scss' => '',
                        'w2.scss' => '',
                    ],
                ],
                [
                    'w1.scss' => [
                        'lintExitCode' => 1,
                        'lintStdOutput' => json_encode($reports['w1'], true),
                        'report' => $reports['w1'],
                    ],
                    'w2.scss' => [
                        'lintExitCode' => 1,
                        'lintStdOutput' => json_encode($reports['w2'], true),
                        'report' => $reports['w2'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesRun
     */
    public function testRun(array $expected, array $options, array $files, array $properties = []): void
    {
        $container = Robo::createDefaultContainer();
        Robo::setContainer($container);

        $outputConfig = [
            'verbosity' => OutputInterface::VERBOSITY_DEBUG,
            'colors' => false,
        ];
        $mainStdOutput = new DummyOutput($outputConfig);

        $properties += ['processClass' => DummyProcess::class];

        /** @var \Sweetchuck\Robo\ScssLint\Task\ScssLintRunInput $task */
        $task = Stub::construct(Task::class, [], $properties);
        $task->setOptions($options);

        $processIndex = count(DummyProcess::$instances);
        foreach ($files as $file) {
            DummyProcess::$prophecy[$processIndex] = [
                'exitCode' => $file['lintExitCode'],
                'stdOutput' => $file['lintStdOutput'],
            ];

            $processIndex++;
        }

        $task->setLogger($container->get('logger'));
        $task->setOutput($mainStdOutput);

        $result = $task->run();

        $this->tester->assertEquals($expected['exitCode'], $result->getExitCode());

        /** @var \Sweetchuck\LintReport\ReportWrapperInterface $reportWrapper */
        $reportWrapper = $result['report'];
        if ($reportWrapper) {
            $this->tester->assertEquals(
                $expected['report'],
                $reportWrapper ? $reportWrapper->getReport() : null
            );
        }
    }
}
