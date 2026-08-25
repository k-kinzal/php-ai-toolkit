<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestClass;

use PhpAiToolkit\PhpStan\Rule\Shared\Path\PathMarkerSplitter;
use PhpAiToolkit\PhpStan\Rule\Shared\Path\SrcUnitTestRelativePathMapper;
use PhpAiToolkit\PhpStan\Rule\TestClass\Pairing\SourceUnitTestFileResolver;
use PhpAiToolkit\PhpStan\Rule\TestClass\PublicMethodTestCoverageErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\TestClass\PublicMethodTestCoverageValidator;
use PhpAiToolkit\PhpStan\Rule\TestClass\TestMethodFileReader;
use PhpParser\Node\Stmt\Class_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\PhpStan\Rule\TestClass\PublicMethodTestCoverageValidator
 * @uses \PhpAiToolkit\PhpStan\Rule\Shared\Path\PathMarkerSplitter
 * @uses \PhpAiToolkit\PhpStan\Rule\TestClass\PublicMethodTestCoverageErrorBuilder
 * @uses \PhpAiToolkit\PhpStan\Rule\TestClass\Pairing\SourceUnitTestFileResolver
 * @uses \PhpAiToolkit\PhpStan\Rule\Shared\Path\SrcUnitTestRelativePathMapper
 * @uses \PhpAiToolkit\PhpStan\Rule\TestClass\TestMethodFileReader
 */
#[CoversClass(PublicMethodTestCoverageValidator::class)]
#[UsesClass(PathMarkerSplitter::class)]
#[UsesClass(PublicMethodTestCoverageErrorBuilder::class)]
#[UsesClass(SourceUnitTestFileResolver::class)]
#[UsesClass(SrcUnitTestRelativePathMapper::class)]
#[UsesClass(TestMethodFileReader::class)]
final class PublicMethodTestCoverageValidatorTest extends TestCase
{
    public function testErrorsReturnsPublicMethodWithoutTestError(): void
    {
        $method = new \PhpParser\Node\Stmt\ClassMethod('getResult', ['flags' => Class_::MODIFIER_PUBLIC]);
        $sourceFile = __DIR__ . '/../../../../Fixture/TestNamingConvention/src/UncoveredService.php';

        $errors = (new PublicMethodTestCoverageValidator())->errors($method, $sourceFile);

        self::assertSame('customRules.publicMethodWithoutTest', $errors[0]->getIdentifier());
    }
}
