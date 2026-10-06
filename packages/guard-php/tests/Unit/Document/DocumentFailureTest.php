<?php

declare(strict_types=1);

namespace Tests\Unit\Document;

use Guard\Document\DocumentFailure;
use Guard\Policy\PolicyException;
use JsonException;
use Nette\Neon\Exception as NeonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \Guard\Document\DocumentFailure
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\FileSet
 * @uses \Guard\Collect\Filesystem\Entry
 * @uses \Guard\Collect\Input
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Collect\Selection
 * @uses \Guard\Collect\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(DocumentFailure::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\DirectoryListing::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\FileRecord::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\FileSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Input::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\InputSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Selection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\StructuredFile::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Extension\PolicyBinding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Reporting\Finding::class)]
final class DocumentFailureTest extends TestCase
{
    /**
     * @dataProvider providerCauses
     */
    #[DataProvider('providerCauses')]
    public function testAtPreservesExceptionCategoryAndPreviousCauseWithTargetPath(RuntimeException|JsonException|NeonException $cause): void
    {
        $exception = (new DocumentFailure())->at('/project/config', $cause);
        self::assertSame($cause, $exception->getPrevious());
        self::assertStringContainsString('/project/config', $exception->getMessage());
        self::assertStringContainsString($cause->getMessage(), $exception->getMessage());
    }

    public function testAtPreservesDocumentExceptionCategories(): void
    {
        $failure = new DocumentFailure();
        self::assertInstanceOf(JsonException::class, $failure->at('json', new JsonException()));
        self::assertInstanceOf(NeonException::class, $failure->at('neon', new NeonException()));
        self::assertInstanceOf(PolicyException::class, $failure->at('other', new RuntimeException()));
    }

    /**
     * @return iterable<string, array{RuntimeException|JsonException|NeonException}>
     */
    public static function providerCauses(): iterable
    {
        yield 'json' => [new JsonException('bad JSON')];
        yield 'neon' => [new NeonException('bad NEON')];
        yield 'runtime' => [new RuntimeException('bad document')];
    }
}
