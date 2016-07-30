<?php

use Cheppers\Robo\Task\ScssLint\TaskScssLintRun;
use Codeception\Util\Stub;

/**
 * Class TaskScssLintRunTest.
 */
// @codingStandardsIgnoreStart
class TaskScssLintRunTest extends \Codeception\Test\Unit
{
    // @codingStandardsIgnoreEnd

    use Cheppers\Robo\Task\ScssLint\LoadTasks;
    use \Robo\TaskAccessor;

    /**
     * @var \League\Container\Container
     */
    protected $container = null;

    // @codingStandardsIgnoreStart
    protected function _before()
    {
        // @codingStandardsIgnoreEnd
        parent::_before();

        $this->container = new \League\Container\Container();
        \Robo\Robo::setContainer($this->container);
        \Robo\Runner::configureContainer($this->container, null, new \Helper\Dummy\Output());
        $this->container->addServiceProvider(static::getScssLintServiceProvider());
    }

    /**
     * @return \League\Container\Container
     */
    public function getContainer()
    {
        return $this->container;
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
        $task = new TaskScssLintRun($options, $paths);
        static::assertEquals($expected, $task->buildCommand());
    }

    public function testExitCodeConstants()
    {
        static::assertEquals(0, TaskScssLintRun::EXIT_CODE_OK);
        static::assertEquals(1, TaskScssLintRun::EXIT_CODE_WARNING);
        static::assertEquals(2, TaskScssLintRun::EXIT_CODE_ERROR);
        static::assertEquals(80, TaskScssLintRun::EXIT_CODE_NO_FILES);
    }

    /**
     * @return array
     */
    public function casesGetTaskExitCode()
    {
        return [
            'never-ok' => [
                TaskScssLintRun::EXIT_CODE_OK,
                [
                    'failOn' => 'never',
                    'failOnNoFiles' => true,
                ],
                TaskScssLintRun::EXIT_CODE_OK,
            ],
            'never-warning' => [
                TaskScssLintRun::EXIT_CODE_OK,
                [
                    'failOn' => 'never',
                    'failOnNoFiles' => true,
                ],
                TaskScssLintRun::EXIT_CODE_WARNING,
            ],
            'never-error' => [
                TaskScssLintRun::EXIT_CODE_OK,
                [
                    'failOn' => 'never',
                    'failOnNoFiles' => true,
                ],
                TaskScssLintRun::EXIT_CODE_ERROR,
            ],
            'never-no-files-false' => [
                TaskScssLintRun::EXIT_CODE_OK,
                [
                    'failOn' => 'never',
                    'failOnNoFiles' => false,
                ],
                TaskScssLintRun::EXIT_CODE_NO_FILES,
            ],
            'never-no-files-true' => [
                TaskScssLintRun::EXIT_CODE_NO_FILES,
                [
                    'failOn' => 'never',
                    'failOnNoFiles' => true,
                ],
                TaskScssLintRun::EXIT_CODE_NO_FILES,
            ],
            'warning-ok' => [
                TaskScssLintRun::EXIT_CODE_OK,
                [
                    'failOn' => 'warning',
                    'failOnNoFiles' => false,
                ],
                TaskScssLintRun::EXIT_CODE_OK,
            ],
            'warning-warning' => [
                TaskScssLintRun::EXIT_CODE_WARNING,
                [
                    'failOn' => 'warning',
                    'failOnNoFiles' => false,
                ],
                TaskScssLintRun::EXIT_CODE_WARNING,
            ],
            'warning-error' => [
                TaskScssLintRun::EXIT_CODE_ERROR,
                [
                    'failOn' => 'warning',
                    'failOnNoFiles' => false,
                ],
                TaskScssLintRun::EXIT_CODE_ERROR,
            ],
            'warning-no-files-false' => [
                TaskScssLintRun::EXIT_CODE_OK,
                [
                    'failOn' => 'warning',
                    'failOnNoFiles' => false,
                ],
                TaskScssLintRun::EXIT_CODE_NO_FILES,
            ],
            'warning-no-files-true' => [
                TaskScssLintRun::EXIT_CODE_NO_FILES,
                [
                    'failOn' => 'warning',
                    'failOnNoFiles' => true,
                ],
                TaskScssLintRun::EXIT_CODE_NO_FILES,
            ],
            'error-ok' => [
                TaskScssLintRun::EXIT_CODE_OK,
                [
                    'failOn' => 'error',
                    'failOnNoFiles' => false,
                ],
                TaskScssLintRun::EXIT_CODE_OK,
            ],
            'error-warning' => [
                TaskScssLintRun::EXIT_CODE_OK,
                [
                    'failOn' => 'error',
                    'failOnNoFiles' => false,
                ],
                TaskScssLintRun::EXIT_CODE_WARNING,
            ],
            'error-error' => [
                TaskScssLintRun::EXIT_CODE_ERROR,
                [
                    'failOn' => 'error',
                    'failOnNoFiles' => false,
                ],
                TaskScssLintRun::EXIT_CODE_ERROR,
            ],
            'error-no-files-false' => [
                TaskScssLintRun::EXIT_CODE_OK,
                [
                    'failOn' => 'error',
                    'failOnNoFiles' => false,
                ],
                TaskScssLintRun::EXIT_CODE_NO_FILES,
            ],
            'error-no-files-true' => [
                TaskScssLintRun::EXIT_CODE_NO_FILES,
                [
                    'failOn' => 'error',
                    'failOnNoFiles' => true,
                ],
                TaskScssLintRun::EXIT_CODE_NO_FILES,
            ],
        ];
    }

    /**
     * @dataProvider casesGetTaskExitCode
     *
     * @param int $expected
     * @param array $options
     * @param int $exitCode
     */
    public function testGetTaskExitCode($expected, $options, $exitCode)
    {
        /** @var TaskScssLintRun $task */
        $task = Stub::construct(
            TaskScssLintRun::class,
            [$options, []],
            ['exitCode' => $exitCode]
        );

        static::assertEquals($expected, $task->getTaskExitCode());
    }

    /**
     * @return array
     */
    public function casesRun()
    {
        return [
            'without asset jar' => [
                0,
                'my-dummy-output',
                false,
            ],
            'with asset jar' => [
                0,
                ['success' => true],
                true,
            ],
        ];
    }

    /**
     * This way cannot be tested those cases when the lint process failed.
     *
     * @dataProvider casesRun
     *
     * @param int $exitCode
     * @param string $stdOutput
     * @param bool $withJar
     */
    public function testRun($exitCode, $stdOutput, $withJar)
    {
        $options = [
            'workingDirectory' => 'my-working-dir',
            'assetJarMapping' => ['report' => ['scssLintRun', 'report']],
            'format' => 'JSON',
        ];

        /** @var TaskScssLintRun $task */
        $task = Stub::construct(
            TaskScssLintRun::class,
            [$options, []],
            [
                'processClass' => \Helper\Dummy\Process::class,
            ]
        );

        \Helper\Dummy\Process::$exitCode = $exitCode;
        \Helper\Dummy\Process::$stdOutput = $withJar ? json_encode($stdOutput) : $stdOutput;

        $task->setLogger($this->container->get('logger'));
        $assetJar = null;
        if ($withJar) {
            $assetJar = new \Cheppers\AssetJar\AssetJar();
            $task->setAssetJar($assetJar);
        }


        $result = $task->run();

        static::assertEquals($exitCode, $result->getExitCode());
        static::assertEquals(
            $options['workingDirectory'],
            \Helper\Dummy\Process::$instance->getWorkingDirectory()
        );

        if ($withJar) {
            static::assertEquals($stdOutput, $assetJar->getValue(['scssLintRun', 'report']));
        } else {
            /** @var \Helper\Dummy\Output $output */
            $output = $this->container->get('output');
            static::assertContains($stdOutput, $output->output);
        }
    }

    public function testRunFailed()
    {
        $exitCode = 2;
        $stdOutput = '{"foo": "bar"}';
        $options = [
            'workingDirectory' => 'my-working-dir',
            'assetJarMapping' => ['report' => ['scssLintRun', 'report']],
            'format' => 'JSON',
        ];

        /** @var TaskScssLintRun $task */
        $task = Stub::construct(
            TaskScssLintRun::class,
            [$options, []],
            [
                'processClass' => \Helper\Dummy\Process::class,
            ]
        );

        \Helper\Dummy\Process::$exitCode = $exitCode;
        \Helper\Dummy\Process::$stdOutput = $stdOutput;

        $task->setLogger($this->container->get('logger'));
        $assetJar = new \Cheppers\AssetJar\AssetJar();
        $task->setAssetJar($assetJar);

        $result = $task->run();

        static::assertEquals($exitCode, $result->getExitCode());
        static::assertEquals(
            $options['workingDirectory'],
            \Helper\Dummy\Process::$instance->getWorkingDirectory()
        );

        static::assertEquals(['foo' => 'bar'], $assetJar->getValue(['scssLintRun', 'report']));
    }

    public function testContainerInstance()
    {
        $task = $this->taskScssLintRun();
        static::assertEquals(0, $task->getTaskExitCode());
    }
}
