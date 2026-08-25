<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestClass\Pairing;

use PhpAiToolkit\PhpStan\Rule\Shared\Path\SrcUnitTestRelativePathMapper;
use PhpAiToolkit\PhpStan\Rule\TestClass\Pairing\FilenameExclusionMatcher;
use PhpAiToolkit\PhpStan\Rule\TestClass\Pairing\SourceFileUnitTestPairInspector;
use PhpAiToolkit\PhpStan\Rule\TestClass\Pairing\SrcUnitTestPairErrorBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\PhpStan\Rule\TestClass\Pairing\SourceFileUnitTestPairInspector
 * @uses \PhpAiToolkit\PhpStan\Rule\TestClass\Pairing\FilenameExclusionMatcher
 * @uses \PhpAiToolkit\PhpStan\Rule\TestClass\Pairing\SrcUnitTestPairErrorBuilder
 * @uses \PhpAiToolkit\PhpStan\Rule\Shared\Path\SrcUnitTestRelativePathMapper
 */
#[CoversClass(SourceFileUnitTestPairInspector::class)]
#[UsesClass(FilenameExclusionMatcher::class)]
#[UsesClass(SrcUnitTestPairErrorBuilder::class)]
#[UsesClass(SrcUnitTestRelativePathMapper::class)]
final class SourceFileUnitTestPairInspectorTest extends TestCase
{
    public function testErrorsReturnsMissingUnitTestError(): void
    {
        $root = sys_get_temp_dir() . '/php-ai-toolkit-missing-source-pair';

        $errors = (new SourceFileUnitTestPairInspector('/src/', '/tests/Unit/'))->errors(
            $root . '/src/Missing.php',
            [$root, 'Missing.php'],
        );

        self::assertSame('customRules.srcWithoutUnitTest', $errors[0]->getIdentifier());
    }
}
