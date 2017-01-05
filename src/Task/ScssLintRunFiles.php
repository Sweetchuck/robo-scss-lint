<?php

namespace Cheppers\Robo\ScssLint\Task;

class ScssLintRunFiles extends ScssLintRun
{
    /**
     * @return string
     */
    protected function getTaskInfoPattern()
    {
        return '{name} runs "<info>{command}</info>" command';
    }

    protected function getTaskContext($context = null)
    {
        return [
            'command' => $this->getCommand(),
        ] + parent::getTaskContext($context);
    }
}
