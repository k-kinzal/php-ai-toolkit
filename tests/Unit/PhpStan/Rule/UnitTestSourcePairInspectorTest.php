<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\SrcUnitTestPairErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\SrcUnitTestRelativePathMapper;
use PhpAiToolkit\PhpStan\Rule\UnitTestSourcePairInspector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UnitTestSourcePairInspector::class)]
#[UsesClass(SrcUnitTestPairErrorBuilder::class)]
#[UsesClass(SrcUnitTestRelativePathMapper::class)]
final class UnitTestSourcePairInspectorTest extends TestCase
{
    public function testErrorsReturnsMissingSourceError(): void
    {
        $root = sys_get_temp_dir() . '/php-ai-toolkit-missing-test-pair';

        $errors = (new UnitTestSourcePairInspector('/src/', '/tests/Unit/'))->errors([$root, 'MissingTest.php']);

        self::assertSame('customRules.unitTestWithoutSource', $errors[0]->getIdentifier());
    }
}
