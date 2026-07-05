<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\FilenameExclusionMatcher;
use PhpAiToolkit\PhpStan\Rule\SourceFileUnitTestPairInspector;
use PhpAiToolkit\PhpStan\Rule\SrcUnitTestPairErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\SrcUnitTestRelativePathMapper;
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
