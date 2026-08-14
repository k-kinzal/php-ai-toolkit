<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestNamingConvention;

use PhpAiToolkit\PhpStan\Rule\Shared\PathMarkerSplitter;
use PhpAiToolkit\PhpStan\Rule\Shared\SrcUnitTestRelativePathMapper;
use PhpAiToolkit\PhpStan\Rule\TestNamingConvention\PublicMethodTestCoverageErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\TestNamingConvention\PublicMethodTestCoverageValidator;
use PhpAiToolkit\PhpStan\Rule\TestNamingConvention\SourceUnitTestFileResolver;
use PhpAiToolkit\PhpStan\Rule\TestNamingConvention\TestMethodFileReader;
use PhpParser\Node\Stmt\Class_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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
