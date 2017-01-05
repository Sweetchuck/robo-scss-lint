<?php

namespace Cheppers\Robo\ScssLint\Test\Acceptance;

use AcceptanceTester;

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

    public function lintFilesAllInOne(AcceptanceTester $I): void
    {
        $roboTaskName = 'lint:files-all-in-one';
        $command = $this->getCommand($roboTaskName);

        $I->wantTo("Run Robo task '<comment>$command</comment>'.");
        $I->runRoboTask($roboTaskName);
        $I->expectTheExitCodeToBe(2);
        $I->seeThisTextInTheStdOutput(file_get_contents("{$this->expectedDir}/extra.verbose.txt"));
        $I->seeThisTextInTheStdOutput(file_get_contents("{$this->expectedDir}/extra.summary.txt"));
        $I->haveAFileLikeThis('extra.verbose.txt');
        $I->haveAFileLikeThis('extra.summary.txt');
        $I->seeThisTextInTheStdError('One or more errors were reported (and any number of warnings)');
    }

    public function lintFilesDefaultFile(AcceptanceTester $I): void
    {
        $roboTaskName = 'lint:files-default-file';
        $command = $this->getCommand($roboTaskName);

        $I->wantTo("Run Robo task '<comment>$command</comment>'.");
        $I->runRoboTask($roboTaskName);
        $I->expectTheExitCodeToBe(2);
        $I->haveAFileLikeThis('native.default.txt');
        $I->seeThisTextInTheStdError('One or more errors were reported (and any number of warnings)');
    }

    public function lintFilesDefaultStdOutput(AcceptanceTester $I): void
    {
        $roboTaskName = 'lint:files-default-std-output';
        $command = $this->getCommand($roboTaskName);

        $I->wantTo("Run Robo task '<comment>$command</comment>'.");
        $I->runRoboTask($roboTaskName);
        $I->expectTheExitCodeToBe(2);
        $I->seeThisTextInTheStdOutput(file_get_contents("{$this->expectedDir}/native.default.txt"));
        $I->seeThisTextInTheStdError('One or more errors were reported (and any number of warnings)');
    }

    public function lintInputWithoutJarTaskCommandOnlyFalse(AcceptanceTester $i): void
    {
        $roboTaskName = 'lint:input-without-jar';
        // @todo https://github.com/Cheppers/robo-phpcs/issues/6
        if (getenv('TRAVIS_OS_NAME') === 'osx') {
            $i->wantTo("Skip the '$roboTaskName' task, because it does not work on OSX");

            return;
        }

        $this->lintInput($i, $roboTaskName);
    }

    public function lintInputWithoutJarTaskCommandOnlyTrue(AcceptanceTester $i): void
    {
        $this->lintInput($i, 'lint:input-without-jar', [], ['command-only' => null]);
    }

    public function lintInputWithJarTaskCommandOnlyFalse(AcceptanceTester $i)
    {
        $roboTaskName = 'lint:input-with-jar';

        // @todo https://github.com/Cheppers/robo-phpcs/issues/6
        if (getenv('TRAVIS_OS_NAME') === 'osx') {
            $i->wantTo("Skip the '$roboTaskName' task, because it does not work on OSX");

            return;
        }

        $this->lintInput($i, $roboTaskName);
    }

    public function lintInputWithJarTaskCommandOnlyTrue(AcceptanceTester $i)
    {
        $this->lintInput($i, 'lint:input-with-jar', [], ['command-only' => null]);
    }

    protected function lintInput(AcceptanceTester $I, $roboTaskName, array $args = [], array $options = []): void
    {
        $command = $this->getCommand($roboTaskName, $args, $options);

        $I->wantTo("Run Robo task '<comment>$command</comment>'.");
        $I->runRoboTask($roboTaskName, $args, $options);
        $I->expectTheExitCodeToBe(2);
        $I->haveAFileLikeThis('input.checkstyle.xml');
        $I->haveAFileLikeThis('input.summary.txt');
        $I->haveAFileLikeThis('input.verbose.txt');
        $I->seeThisTextInTheStdError('One or more errors were reported (and any number of warnings)');
    }

    protected function getCommand(string $roboTaskName, array $args = [], array $options = []): string
    {
        $cmdPattern = '%s';
        $cmdArgs = [
            escapeshellarg($roboTaskName),
        ];

        foreach ($options as $option => $value) {
            $cmdPattern .= " --$option";
            if ($value !== null) {
                $cmdPattern .= '=%s';
                $cmdArgs[] = escapeshellarg($value);
            }
        }

        $cmdPattern .= str_repeat(' %s', count($args));
        foreach ($args as $arg) {
            $cmdArgs[] = escapeshellarg($arg);
        }

        return vsprintf($cmdPattern, $cmdArgs);
    }
}
