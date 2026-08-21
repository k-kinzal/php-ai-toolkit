<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestClass;

use PhpAiToolkit\PhpStan\Rule\Shared\PathMarkerSplitter;
use PhpAiToolkit\PhpStan\Rule\Shared\RulePathNormalizer;
use PhpAiToolkit\PhpStan\Rule\Shared\SrcUnitTestRelativePathMapper;
use PhpAiToolkit\PhpStan\Rule\TestClass\FilenameExclusionMatcher;
use PhpAiToolkit\PhpStan\Rule\TestClass\SourceFileUnitTestPairInspector;
use PhpAiToolkit\PhpStan\Rule\TestClass\SrcUnitTestPairErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\TestClass\SrcUnitTestPairFileInspector;
use PhpAiToolkit\PhpStan\Rule\TestClass\UnitTestSourcePairInspector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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
