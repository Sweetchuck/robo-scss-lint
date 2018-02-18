<?php

namespace Sweetchuck\Robo\ScssLint\Task;

class ScssLintRunFiles extends ScssLintRun
{
    /**
     * {@inheritdoc}
     */
    protected $taskName = 'SCSS lint - Files';

    /**
     * {@inheritdoc}
     */
    protected function getTaskInfoPattern()
    {
        return 'runs "<info>{command}</info>" command';
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
