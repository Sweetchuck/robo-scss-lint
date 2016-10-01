<?php

namespace Cheppers\Robo\ScssLint\LintReportWrapper;

use Cheppers\LintReport\FailureWrapperInterface;

/**
 * Class FileWrapper.
 *
 * @package Cheppers\LintReport\Wrapper\ScssLint
 */
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
     * @return string
     */
    public function severity()
    {
        return $this->failure['severity'];
    }

    /**
     * @return string
     */
    public function source()
    {
        return $this->failure['linter'];
    }

    /**
     * @return int
     */
    public function line()
    {
        return $this->failure['line'];
    }

    /**
     * @return int
     */
    public function column()
    {
        return $this->failure['column'];
    }

    /**
     * @return string
     */
    public function message()
    {
        return $this->failure['reason'];
    }
}
