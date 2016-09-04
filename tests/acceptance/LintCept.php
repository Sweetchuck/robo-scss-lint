<?php
/**
 * @var \Codeception\Scenario $scenario
 */

$dataDir = rtrim(codecept_data_dir(), '/');

$i = new AcceptanceTester($scenario);
$i->wantTo('Run TaskScssLintRun Robo task');
$i
    ->runRoboTask('lint')
    ->theExitCodeShouldBe(1)
    ->seeThisTextInTheStdOutput('"fixtures/invalid.scss": [');
