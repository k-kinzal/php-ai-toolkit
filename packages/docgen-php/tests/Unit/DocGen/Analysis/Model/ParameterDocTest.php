<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Model\ParameterDoc;
use Toolkit\DocGen\Analysis\Model\TypeSignature;

/**
 * @covers \Toolkit\DocGen\Analysis\Model\ParameterDoc
 * @uses \Toolkit\DocGen\Analysis\Model\TypeSignature
 */
#[CoversClass(ParameterDoc::class)]
#[UsesClass(TypeSignature::class)]
final class ParameterDocTest extends TestCase
{
    public function testStoresParameterData(): void
    {
        $type = new TypeSignature('string', null);

        $parameter = new ParameterDoc('name', $type, true, false, "'unnamed'", 'private', 'the widget name');

        self::assertSame('name', $parameter->name);
        self::assertSame($type, $parameter->type);
        self::assertTrue($parameter->byRef);
        self::assertFalse($parameter->variadic);
        self::assertSame("'unnamed'", $parameter->defaultText);
        self::assertSame('private', $parameter->promotedVisibility);
        self::assertSame('the widget name', $parameter->description);
    }

    public function testStoresAbsentOptionalsAsNull(): void
    {
        $type = new TypeSignature(null, null);

        $parameter = new ParameterDoc('values', $type, false, true, null, null, '');

        self::assertSame('values', $parameter->name);
        self::assertSame($type, $parameter->type);
        self::assertFalse($parameter->byRef);
        self::assertTrue($parameter->variadic);
        self::assertNull($parameter->defaultText);
        self::assertNull($parameter->promotedVisibility);
        self::assertSame('', $parameter->description);
    }
}
