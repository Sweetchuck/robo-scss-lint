<?php

namespace Sweetchuck\Robo\ScssLint\Tests\Acceptance;

use Sweetchuck\Robo\ScssLint\Test\AcceptanceTester;
use Sweetchuck\Robo\ScssLint\Test\Helper\RoboFiles\ScssLintRoboFile;

class RunRoboTaskCest
{
    /**
     * @var string
     */
    protected $expectedDir = '';

    public function __construct()
    {
        $this->expectedDir = codecept_data_dir('expected');
    }

    // @codingStandardsIgnoreStart
    public function _before(AcceptanceTester $I): void
    {
        // @codingStandardsIgnoreEnd
        $I->clearTheReportsDir();
    }

    public function lintFilesAllInOne(AcceptanceTester $i): void
    {
        $id = __METHOD__;

        $i->runRoboTask(
            $id,
            ScssLintRoboFile::class,
            'lint:files-all-in-one'
        );

        $exitCode = $i->getRoboTaskExitCode($id);
        $stdOutput = $i->getRoboTaskStdOutput($id);
        $stdError = $i->getRoboTaskStdError($id);

        $i->assertEquals(2, $exitCode);
        $i->assertContains(
            file_get_contents("{$this->expectedDir}/extra.verbose.txt"),
            $stdOutput
        );
        $i->assertContains(
            file_get_contents("{$this->expectedDir}/extra.summary.txt"),
            $stdOutput
        );
        $i->assertContains(
            'One or more errors were reported (and any number of warnings)',
            $stdError
        );

        $i->haveAFileLikeThis('extra.verbose.txt');
        $i->haveAFileLikeThis('extra.summary.txt');
    }

    public function lintFilesDefaultFile(AcceptanceTester $i): void
    {
        $id = __METHOD__;

        $i->runRoboTask(
            $id,
            ScssLintRoboFile::class,
            'lint:files-default-file'
        );

        $exitCode = $i->getRoboTaskExitCode($id);
        $stdOutput = $i->getRoboTaskStdOutput($id);
        $stdError = $i->getRoboTaskStdError($id);

        $i->assertEquals(2, $exitCode);
        $i->assertEquals(
            '',
            $stdOutput,
            'stdOutput equals'
        );

        $i->assertContains(
            'One or more errors were reported (and any number of warnings)',
            $stdError,
            'stdError contains'
        );
        $i->haveAFileLikeThis('native.default.txt');
    }

    public function lintFilesDefaultStdOutput(AcceptanceTester $i): void
    {
        $id = __METHOD__;

        $i->runRoboTask(
            $id,
            ScssLintRoboFile::class,
            'lint:files-default-std-output'
        );

        $exitCode = $i->getRoboTaskExitCode($id);
        $stdOutput = $i->getRoboTaskStdOutput($id);
        $stdError = $i->getRoboTaskStdError($id);

        $i->assertEquals(2, $exitCode);

        $i->assertContains(
            file_get_contents("{$this->expectedDir}/native.default.txt"),
            $stdOutput
        );
        $i->assertContains(
            'One or more errors were reported (and any number of warnings)',
            $stdError
        );
    }

    public function lintInputTaskCommandOnlyFalse(AcceptanceTester $i): void
    {
        $roboTaskName = 'lint:input';
        // @todo https://github.com/Sweetchuck/robo-phpcs/issues/6
        if (getenv('TRAVIS_OS_NAME') === 'osx') {
            $i->wantTo("Skip the '$roboTaskName' task, because it does not work on OSX");

            return;
        }

        $this->lintInput($i, $roboTaskName);
    }

    public function lintInputTaskCommandOnlyTrue(AcceptanceTester $i): void
    {
        $this->lintInput($i, 'lint:input', ['--command-only']);
    }

    protected function lintInput(AcceptanceTester $i, string $roboTaskName, array $argsAndOptions = []): void
    {
        $id = "$roboTaskName " . implode(' ', $argsAndOptions);

        $i->wantTo("Run Robo task '<comment>$id</comment>'.");

        $i->runRoboTask(
            $id,
            ScssLintRoboFile::class,
            $roboTaskName,
            ...$argsAndOptions
        );

        $exitCode = $i->getRoboTaskExitCode($id);
        $stdError = $i->getRoboTaskStdError($id);

        $i->assertEquals(2, $exitCode);
        $i->assertContains('One or more errors were reported (and any number of warnings)', $stdError);

        $i->haveAFileLikeThis('input.checkstyle.xml');
        $i->haveAFileLikeThis('input.summary.txt');
        $i->haveAFileLikeThis('input.verbose.txt');
    }
}
