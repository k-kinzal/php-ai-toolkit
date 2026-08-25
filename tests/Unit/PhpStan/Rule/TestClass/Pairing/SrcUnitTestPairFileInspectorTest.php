<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestClass\Pairing;

use PhpAiToolkit\PhpStan\Rule\Shared\Path\PathMarkerSplitter;
use PhpAiToolkit\PhpStan\Rule\Shared\Path\RulePathNormalizer;
use PhpAiToolkit\PhpStan\Rule\Shared\Path\SrcUnitTestRelativePathMapper;
use PhpAiToolkit\PhpStan\Rule\TestClass\Pairing\FilenameExclusionMatcher;
use PhpAiToolkit\PhpStan\Rule\TestClass\Pairing\SourceFileUnitTestPairInspector;
use PhpAiToolkit\PhpStan\Rule\TestClass\Pairing\SrcUnitTestPairErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\TestClass\Pairing\SrcUnitTestPairFileInspector;
use PhpAiToolkit\PhpStan\Rule\TestClass\Pairing\UnitTestSourcePairInspector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\PhpStan\Rule\TestClass\Pairing\SrcUnitTestPairFileInspector
 * @uses \PhpAiToolkit\PhpStan\Rule\TestClass\Pairing\FilenameExclusionMatcher
 * @uses \PhpAiToolkit\PhpStan\Rule\Shared\Path\PathMarkerSplitter
 * @uses \PhpAiToolkit\PhpStan\Rule\Shared\Path\RulePathNormalizer
 * @uses \PhpAiToolkit\PhpStan\Rule\TestClass\Pairing\SourceFileUnitTestPairInspector
 * @uses \PhpAiToolkit\PhpStan\Rule\TestClass\Pairing\SrcUnitTestPairErrorBuilder
 * @uses \PhpAiToolkit\PhpStan\Rule\Shared\Path\SrcUnitTestRelativePathMapper
 * @uses \PhpAiToolkit\PhpStan\Rule\TestClass\Pairing\UnitTestSourcePairInspector
 */
#[CoversClass(SrcUnitTestPairFileInspector::class)]
#[UsesClass(FilenameExclusionMatcher::class)]
#[UsesClass(PathMarkerSplitter::class)]
#[UsesClass(RulePathNormalizer::class)]
#[UsesClass(SourceFileUnitTestPairInspector::class)]
#[UsesClass(SrcUnitTestPairErrorBuilder::class)]
#[UsesClass(SrcUnitTestRelativePathMapper::class)]
#[UsesClass(UnitTestSourcePairInspector::class)]
final class SrcUnitTestPairFileInspectorTest extends TestCase
{
    public function testErrorsReturnsMissingUnitTestError(): void
    {
        $file = sys_get_temp_dir() . '/php-ai-toolkit-missing-file-pair/src/Missing.php';

        $errors = (new SrcUnitTestPairFileInspector())->errors($file);

        self::assertSame('customRules.srcWithoutUnitTest', $errors[0]->getIdentifier());
    }
}
