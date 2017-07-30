<?php

namespace Sweetchuck\Robo\ScssLint\Tests\Unit\LintReportWrapper;

use Sweetchuck\Robo\ScssLint\LintReportWrapper\FileWrapper;
use Codeception\Test\Unit;

class FileWrapperTest extends Unit
{
    /**
     * @var \Sweetchuck\Robo\ScssLint\Test\UnitTester
     */
    protected $tester;

    public function casesSeverityComparer(): array
    {
        return [
            'u u' => [0, '?', '?'],
            'o u' => [1, 'ok', '?'],
            'u o' => [-1, '?', 'ok'],

            'o o' => [0, 'ok', 'ok'],
            'o w' => [-1, 'ok', 'warning'],
            'o e' => [-1, 'ok', 'error'],

            'w o' => [1, 'warning', 'ok'],
            'w w' => [0, 'warning', 'warning'],
            'w e' => [-1, 'warning', 'error'],

            'e o' => [1, 'error', 'ok'],
            'e w' => [1, 'error', 'warning'],
            'e e' => [0, 'error', 'error'],
        ];
    }

    /**
     * @dataProvider casesSeverityComparer
     */
    public function testSeverityComparer(int $expected, string $a, string $b): void
    {
        $fw = new FileWrapper([]);
        $class = new \ReflectionClass(FileWrapper::class);
        $severityComparer = $class->getMethod('severityComparer');
        $severityComparer->setAccessible(true);

        $this->tester->assertEquals($expected, $severityComparer->invoke($fw, $a, $b));
    }
}
