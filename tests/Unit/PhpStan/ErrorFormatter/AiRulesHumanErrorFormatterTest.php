<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\ErrorFormatter;

use function dirname;

use PhpAiToolkit\PhpStan\ErrorFormatter\AiRulesHumanErrorFormatter;
use PhpAiToolkit\PhpStan\ErrorFormatter\ErrorCollectionSummary;
use PhpAiToolkit\PhpStan\ErrorFormatter\ErrorGrouping;
use PhpAiToolkit\PhpStan\ErrorFormatter\ErrorGutter;
use PhpAiToolkit\PhpStan\ErrorFormatter\ErrorSourceReader;
use PHPStan\Analyser\Error;
use PHPStan\Command\AnalysisResult;
use PHPStan\File\SimpleRelativePathHelper;
use PHPStan\Testing\ErrorFormatterTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AiRulesHumanErrorFormatter::class)]
final class AiRulesHumanErrorFormatterTest extends ErrorFormatterTestCase
{
    public function testFormatWritesHumanFileBlockAndSummary(): void
    {
        $formatter = new AiRulesHumanErrorFormatter(
            new SimpleRelativePathHelper(dirname(__DIR__, 3)),
            new ErrorSourceReader(),
            new ErrorGutter(),
            new ErrorGrouping(),
            new ErrorCollectionSummary(),
        );
        $file = __DIR__ . '/../../../Fixture/ErrorFormatter/SampleSource.php';

        $formatter->format(new AnalysisResult([
            new Error('Property.', $file, 9, true, null, null, 'Remove it.', null, null, 'custom.a'),
        ], [], [], [], [], false, null, true, 0, false, []), $this->getOutput());

        self::assertStringContainsString('SampleSource.php', $this->getOutputContent());
        self::assertStringContainsString('Found 1 error', $this->getOutputContent());
    }
}
