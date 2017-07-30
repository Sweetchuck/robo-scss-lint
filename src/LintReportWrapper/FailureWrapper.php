<?php

namespace Sweetchuck\Robo\ScssLint\LintReportWrapper;

use Sweetchuck\LintReport\FailureWrapperInterface;

class FailureWrapper implements FailureWrapperInterface
{
    /**
     * @var array
     */
    protected $failure = [];

    /**
     * {@inheritdoc}
     */
    public function __construct(array $failure)
    {
        // @todo Validate.
        $this->failure = $failure + [
            'reason' => '',
            'linter' => '',
            'severity' => '',
            'line' => 0,
            'column' => 0,
            'length' => 0,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function severity(): string
    {
        return $this->failure['severity'];
    }

    /**
     * {@inheritdoc}
     */
    public function source(): string
    {
        return $this->failure['linter'];
    }

    /**
     * {@inheritdoc}
     */
    public function line(): int
    {
        return $this->failure['line'];
    }

    /**
     * {@inheritdoc}
     */
    public function column(): int
    {
        return $this->failure['column'];
    }

    /**
     * {@inheritdoc}
     */
    public function message(): string
    {
        return $this->failure['reason'];
    }
}
