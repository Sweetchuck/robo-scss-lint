<?php

use Cheppers\Robo\ScssLint\Task\Run as ScssLintRunner;
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
    use \Robo\TaskAccessor;
    use \Robo\Common\BuilderAwareTrait;

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
        $task = new ScssLintRunner($options, $paths);
        static::assertEquals($expected, $task->buildCommand());
    }

    public function testExitCodeConstants()
    {
        static::assertEquals(0, ScssLintRunner::EXIT_CODE_OK);
        static::assertEquals(1, ScssLintRunner::EXIT_CODE_WARNING);
        static::assertEquals(2, ScssLintRunner::EXIT_CODE_ERROR);
        static::assertEquals(80, ScssLintRunner::EXIT_CODE_NO_FILES);
    }

    /**
     * @return array
     */
    public function casesGetTaskExitCode()
    {
        return [
            'never-ok' => [
                ScssLintRunner::EXIT_CODE_OK,
                [
                    'failOn' => 'never',
                    'failOnNoFiles' => true,
                ],
                ScssLintRunner::EXIT_CODE_OK,
            ],
            'never-warning' => [
                ScssLintRunner::EXIT_CODE_OK,
                [
                    'failOn' => 'never',
                    'failOnNoFiles' => true,
                ],
                ScssLintRunner::EXIT_CODE_WARNING,
            ],
            'never-error' => [
                ScssLintRunner::EXIT_CODE_OK,
                [
                    'failOn' => 'never',
                    'failOnNoFiles' => true,
                ],
                ScssLintRunner::EXIT_CODE_ERROR,
            ],
            'never-no-files-false' => [
                ScssLintRunner::EXIT_CODE_OK,
                [
                    'failOn' => 'never',
                    'failOnNoFiles' => false,
                ],
                ScssLintRunner::EXIT_CODE_NO_FILES,
            ],
            'never-no-files-true' => [
                ScssLintRunner::EXIT_CODE_NO_FILES,
                [
                    'failOn' => 'never',
                    'failOnNoFiles' => true,
                ],
                ScssLintRunner::EXIT_CODE_NO_FILES,
            ],
            'warning-ok' => [
                ScssLintRunner::EXIT_CODE_OK,
                [
                    'failOn' => 'warning',
                    'failOnNoFiles' => false,
                ],
                ScssLintRunner::EXIT_CODE_OK,
            ],
            'warning-warning' => [
                ScssLintRunner::EXIT_CODE_WARNING,
                [
                    'failOn' => 'warning',
                    'failOnNoFiles' => false,
                ],
                ScssLintRunner::EXIT_CODE_WARNING,
            ],
            'warning-error' => [
                ScssLintRunner::EXIT_CODE_ERROR,
                [
                    'failOn' => 'warning',
                    'failOnNoFiles' => false,
                ],
                ScssLintRunner::EXIT_CODE_ERROR,
            ],
            'warning-no-files-false' => [
                ScssLintRunner::EXIT_CODE_OK,
                [
                    'failOn' => 'warning',
                    'failOnNoFiles' => false,
                ],
                ScssLintRunner::EXIT_CODE_NO_FILES,
            ],
            'warning-no-files-true' => [
                ScssLintRunner::EXIT_CODE_NO_FILES,
                [
                    'failOn' => 'warning',
                    'failOnNoFiles' => true,
                ],
                ScssLintRunner::EXIT_CODE_NO_FILES,
            ],
            'error-ok' => [
                ScssLintRunner::EXIT_CODE_OK,
                [
                    'failOn' => 'error',
                    'failOnNoFiles' => false,
                ],
                ScssLintRunner::EXIT_CODE_OK,
            ],
            'error-warning' => [
                ScssLintRunner::EXIT_CODE_OK,
                [
                    'failOn' => 'error',
                    'failOnNoFiles' => false,
                ],
                ScssLintRunner::EXIT_CODE_WARNING,
            ],
            'error-error' => [
                ScssLintRunner::EXIT_CODE_ERROR,
                [
                    'failOn' => 'error',
                    'failOnNoFiles' => false,
                ],
                ScssLintRunner::EXIT_CODE_ERROR,
            ],
            'error-no-files-false' => [
                ScssLintRunner::EXIT_CODE_OK,
                [
                    'failOn' => 'error',
                    'failOnNoFiles' => false,
                ],
                ScssLintRunner::EXIT_CODE_NO_FILES,
            ],
            'error-no-files-true' => [
                ScssLintRunner::EXIT_CODE_NO_FILES,
                [
                    'failOn' => 'error',
                    'failOnNoFiles' => true,
                ],
                ScssLintRunner::EXIT_CODE_NO_FILES,
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
        /** @var ScssLintRunner $task */
        $task = Stub::construct(
            ScssLintRunner::class,
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

        /** @var ScssLintRunner $task */
        $task = Stub::construct(
            ScssLintRunner::class,
            [$options, []],
            [
                'processClass' => \Helper\Dummy\Process::class,
            ]
        );

        $output = new \Helper\Dummy\Output();
        \Helper\Dummy\Process::$exitCode = $exitCode;
        \Helper\Dummy\Process::$stdOutput = $withJar ? json_encode($stdOutput) : $stdOutput;

        $task->setLogger($this->container->get('logger'));
        $task->setOutput($output);
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
            static::assertEquals(
                $stdOutput,
                $assetJar->getValue(['scssLintRun', 'report']),
                'Output equals'
            );
        } else {
            static::assertContains(
                $stdOutput,
                $output->output,
                'Output contains'
            );
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

        /** @var ScssLintRunner $task */
        $task = Stub::construct(
            ScssLintRunner::class,
            [$options, []],
            [
                'processClass' => \Helper\Dummy\Process::class,
            ]
        );

        \Helper\Dummy\Process::$exitCode = $exitCode;
        \Helper\Dummy\Process::$stdOutput = $stdOutput;

        $task->setConfig(\Robo\Robo::config());
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
}
