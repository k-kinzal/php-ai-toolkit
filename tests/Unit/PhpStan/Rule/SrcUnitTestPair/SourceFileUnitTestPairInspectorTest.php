<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\SrcUnitTestPair;

use PhpAiToolkit\PhpStan\Rule\Shared\SrcUnitTestRelativePathMapper;
use PhpAiToolkit\PhpStan\Rule\SrcUnitTestPair\FilenameExclusionMatcher;
use PhpAiToolkit\PhpStan\Rule\SrcUnitTestPair\SourceFileUnitTestPairInspector;
use PhpAiToolkit\PhpStan\Rule\SrcUnitTestPair\SrcUnitTestPairErrorBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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
