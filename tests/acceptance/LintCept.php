<?php
/**
 * @var \Codeception\Scenario $scenario
 */

$dataDir = rtrim(codecept_data_dir(), '/');

$i = new AcceptanceTester($scenario);
$i->wantTo('Run TaskScssLintRun Robo task');
$cmd = sprintf('bin/robo --load-from %s lint', escapeshellarg($dataDir));
$i->runShellCommand($cmd, false);
$i->seeInShellOutput('"fixtures/invalid.scss": [');
