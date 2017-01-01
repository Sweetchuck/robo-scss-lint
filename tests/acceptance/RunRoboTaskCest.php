<?php

namespace Cheppers\Robo\ScssLint\Test\Acceptance;

use AcceptanceTester;

class RunRoboTaskCest
{
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

    public function lintAllInOne(AcceptanceTester $I): void
    {
        $I->runRoboTask('lint:all-in-one');
        $I->expectTheExitCodeToBe(2);
        $I->seeThisTextInTheStdOutput(file_get_contents("{$this->expectedDir}/extra.verbose.txt"));
        $I->seeThisTextInTheStdOutput(file_get_contents("{$this->expectedDir}/extra.summary.txt"));
        $I->haveAFileLikeThis('extra.verbose.txt');
        $I->haveAFileLikeThis('extra.summary.txt');
        $I->seeThisTextInTheStdError('One or more errors were reported (and any number of warnings)');
    }

    public function lintDefaultFile(AcceptanceTester $I): void
    {
        $I->runRoboTask('lint:default-file');
        $I->expectTheExitCodeToBe(2);
        $I->haveAFileLikeThis('native.default.txt');
        $I->seeThisTextInTheStdError('One or more errors were reported (and any number of warnings)');
    }

    public function lintDefaultStdOutput(AcceptanceTester $I): void
    {
        $I->runRoboTask('lint:default-std-output');
        $I->expectTheExitCodeToBe(2);
        $I->seeThisTextInTheStdOutput(file_get_contents("{$this->expectedDir}/native.default.txt"));
        $I->seeThisTextInTheStdError('One or more errors were reported (and any number of warnings)');
    }
}
