<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestClass\Pairing;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Shared\Path\PathMarkerSplitter;
use Toolkit\PhpStan\Rule\Shared\Path\RulePathNormalizer;
use Toolkit\PhpStan\Rule\Shared\Path\SrcUnitTestRelativePathMapper;
use Toolkit\PhpStan\Rule\TestClass\Pairing\FilenameExclusionMatcher;
use Toolkit\PhpStan\Rule\TestClass\Pairing\SourceFileUnitTestPairInspector;
use Toolkit\PhpStan\Rule\TestClass\Pairing\SrcUnitTestPairErrorBuilder;
use Toolkit\PhpStan\Rule\TestClass\Pairing\SrcUnitTestPairFileInspector;
use Toolkit\PhpStan\Rule\TestClass\Pairing\UnitTestSourcePairInspector;

/**
 * @covers \Toolkit\PhpStan\Rule\TestClass\Pairing\SrcUnitTestPairFileInspector
 * @uses \Toolkit\PhpStan\Rule\TestClass\Pairing\FilenameExclusionMatcher
 * @uses \Toolkit\PhpStan\Rule\Shared\Path\PathMarkerSplitter
 * @uses \Toolkit\PhpStan\Rule\Shared\Path\RulePathNormalizer
 * @uses \Toolkit\PhpStan\Rule\TestClass\Pairing\SourceFileUnitTestPairInspector
 * @uses \Toolkit\PhpStan\Rule\TestClass\Pairing\SrcUnitTestPairErrorBuilder
 * @uses \Toolkit\PhpStan\Rule\Shared\Path\SrcUnitTestRelativePathMapper
 * @uses \Toolkit\PhpStan\Rule\TestClass\Pairing\UnitTestSourcePairInspector
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
