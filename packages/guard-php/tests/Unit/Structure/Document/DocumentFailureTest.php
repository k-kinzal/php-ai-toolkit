<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Document;

use Guard\Diagnostic\PolicyException;
use Guard\Structure\Document\DocumentFailure;
use JsonException;
use Nette\Neon\Exception as NeonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \Guard\Structure\Document\DocumentFailure
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Diagnostic\Finding
 */
#[CoversClass(DocumentFailure::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\DirectoryListing::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\FileRecord::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\FileSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Entry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Input::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\InputSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Selection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\StructuredFile::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\PolicyBinding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Diagnostic\Finding::class)]
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
