<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\ErrorFormatter;

use function dirname;

use PHPStan\Analyser\Error;
use PHPStan\Command\AnalysisResult;
use PHPStan\File\SimpleRelativePathHelper;
use PHPStan\Testing\ErrorFormatterTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use Toolkit\PhpStan\ErrorFormatter\AiErrorRenderer;
use Toolkit\PhpStan\ErrorFormatter\ErrorCollectionSummary;
use Toolkit\PhpStan\ErrorFormatter\ErrorGrouping;
use Toolkit\PhpStan\ErrorFormatter\ErrorSourceReader;

/**
 * @covers \Toolkit\PhpStan\ErrorFormatter\AiErrorRenderer
 */
#[CoversClass(AiErrorRenderer::class)]
#[Medium]
final class AiErrorRendererTest extends ErrorFormatterTestCase
{
    public function testFormatWritesAiHeaderAndFlatErrors(): void
    {
        $formatter = new AiErrorRenderer(
            new SimpleRelativePathHelper(dirname(__DIR__, 3)),
            new ErrorSourceReader(),
            new ErrorGrouping(),
            new ErrorCollectionSummary(),
        );
        $file = __DIR__ . '/../../../Fixture/ErrorFormatter/SampleSource.php';

        $formatter->format(new AnalysisResult([
            new Error('Property.', $file, 9, true, null, null, 'Remove it.', null, null, 'custom.a'),
        ], [], [], [], [], false, null, true, 0, false, []), $this->getOutput());

        self::assertStringContainsString('--- 1 error in 1 file ---', $this->getOutputContent());
        self::assertStringContainsString('[custom.a]', $this->getOutputContent());
    }

    public function testFlatWritesOneBlockPerError(): void
    {
        $formatter = new AiErrorRenderer(
            new SimpleRelativePathHelper(dirname(__DIR__, 3)),
            new ErrorSourceReader(),
            new ErrorGrouping(),
            new ErrorCollectionSummary(),
        );
        $file = __DIR__ . '/../../../Fixture/ErrorFormatter/SampleSource.php';

        $formatter->flat($this->getOutput(), [
            new Error('Property.', $file, 9, true, null, null, null, null, null, 'custom.a'),
        ]);

        self::assertStringContainsString('SampleSource.php:9 [custom.a]', $this->getOutputContent());
    }

    public function testDeduplicatedWritesIdentifierGroup(): void
    {
        $formatter = new AiErrorRenderer(
            new SimpleRelativePathHelper(dirname(__DIR__, 3)),
            new ErrorSourceReader(),
            new ErrorGrouping(),
            new ErrorCollectionSummary(),
        );
        $file = __DIR__ . '/../../../Fixture/ErrorFormatter/SampleSource.php';

        $formatter->deduplicated($this->getOutput(), [
            new Error('Property.', $file, 9, true, null, null, null, null, null, 'custom.a'),
        ]);

        self::assertStringContainsString('[custom.a] 1 occurrence:', $this->getOutputContent());
    }
}
