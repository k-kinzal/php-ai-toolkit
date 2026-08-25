<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestClass\Pairing;

use PhpAiToolkit\PhpStan\Rule\Shared\Path\SrcUnitTestRelativePathMapper;
use PhpAiToolkit\PhpStan\Rule\TestClass\Pairing\SrcUnitTestPairErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\TestClass\Pairing\UnitTestSourcePairInspector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\PhpStan\Rule\TestClass\Pairing\UnitTestSourcePairInspector
 * @uses \PhpAiToolkit\PhpStan\Rule\TestClass\Pairing\SrcUnitTestPairErrorBuilder
 * @uses \PhpAiToolkit\PhpStan\Rule\Shared\Path\SrcUnitTestRelativePathMapper
 */
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
