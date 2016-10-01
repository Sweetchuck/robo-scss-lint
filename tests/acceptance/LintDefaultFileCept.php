<?php

/**
 * @var \Codeception\Scenario $scenario
 */

$roboTaskName = 'lint:default-file';

$i = new AcceptanceTester($scenario);
$i->wantTo("Run Robo task '<comment>$roboTaskName</comment>'.");
$i
    ->clearTheReportsDir()
    ->runRoboTask($roboTaskName)
    ->expectTheExitCodeToBe(2)
    ->haveAFileLikeThis('native.default.txt')
    ->seeThisTextInTheStdError('One or more errors were reported (and any number of warnings)');
