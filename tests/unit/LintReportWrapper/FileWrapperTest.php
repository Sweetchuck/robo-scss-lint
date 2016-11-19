<?php

// @codingStandardsIgnoreStart
use Cheppers\Robo\ScssLint\LintReportWrapper\FileWrapper;

class FileWrapperTest extends \Codeception\Test\Unit
{
    // @codingStandardsIgnoreEnd

    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @return array
     */
    public function casesSeverityComparer()
    {
        return [
            'u u' => [0, '?', '?'],
            'o u' => [1, 'ok', '?'],
            'u o' => [-1, '?', 'ok'],

            'o o' => [0, 'ok', 'ok'],
            'o w' => [1, 'ok', 'warning'],
            'o e' => [1, 'ok', 'error'],

            'w o' => [-1, 'warning', 'ok'],
            'w w' => [0, 'warning', 'warning'],
            'w e' => [1, 'warning', 'error'],

            'e o' => [-1, 'error', 'ok'],
            'e w' => [-1, 'error', 'warning'],
            'e e' => [0, 'error', 'error'],
        ];
    }

    /**
     * @dataProvider casesSeverityComparer
     *
     * @param int $expected
     * @param string $a
     * @param string $b
     */
    public function testSeverityComparer($expected, $a, $b)
    {
        $fw = new FileWrapper([]);
        $class = new ReflectionClass(FileWrapper::class);
        $severityComparer = $class->getMethod('severityComparer');
        $severityComparer->setAccessible(true);

        $this->tester->assertEquals($expected, $severityComparer->invoke($fw, $a, $b));
    }
}
