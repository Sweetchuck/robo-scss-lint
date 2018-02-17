<?php

namespace Sweetchuck\Robo\ScssLint\Task;

class ScssLintRunFiles extends ScssLintRun
{
    /**
     * {@inheritdoc}
     */
    protected function getTaskInfoPattern()
    {
        return '{name} runs "<info>{command}</info>" command';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTaskContext($context = null)
    {
        return [
            'command' => $this->getCommand(),
        ] + parent::getTaskContext($context);
    }
}
