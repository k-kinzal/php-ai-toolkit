<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestClass\Pairing;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Shared\Path\SrcUnitTestRelativePathMapper;
use Toolkit\PhpStan\Rule\TestClass\Pairing\SrcUnitTestPairErrorBuilder;
use Toolkit\PhpStan\Rule\TestClass\Pairing\UnitTestSourcePairInspector;

/**
 * @covers \Toolkit\PhpStan\Rule\TestClass\Pairing\UnitTestSourcePairInspector
 * @uses \Toolkit\PhpStan\Rule\TestClass\Pairing\SrcUnitTestPairErrorBuilder
 * @uses \Toolkit\PhpStan\Rule\Shared\Path\SrcUnitTestRelativePathMapper
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
