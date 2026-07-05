<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\PathMarkerSplitter;
use PhpAiToolkit\PhpStan\Rule\PublicMethodTestCoverageErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\PublicMethodTestCoverageValidator;
use PhpAiToolkit\PhpStan\Rule\SourceUnitTestFileResolver;
use PhpAiToolkit\PhpStan\Rule\SrcUnitTestRelativePathMapper;
use PhpAiToolkit\PhpStan\Rule\TestMethodFileReader;
use PhpParser\Modifiers;
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
        $method = new \PhpParser\Node\Stmt\ClassMethod('getResult', ['flags' => Modifiers::PUBLIC]);
        $sourceFile = __DIR__ . '/../../../Fixture/TestNamingConvention/src/UncoveredService.php';

        $errors = (new PublicMethodTestCoverageValidator())->errors($method, $sourceFile);

        self::assertSame('customRules.publicMethodWithoutTest', $errors[0]->getIdentifier());
    }
}
