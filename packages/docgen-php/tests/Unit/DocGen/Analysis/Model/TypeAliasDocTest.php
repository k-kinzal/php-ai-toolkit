<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Model;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Model\TypeAliasDoc;

/**
 * @covers \Toolkit\DocGen\Analysis\Model\TypeAliasDoc
 */
#[CoversClass(TypeAliasDoc::class)]
final class TypeAliasDocTest extends TestCase
{
    public function testStoresLocalAliasData(): void
    {
        $type = new IdentifierTypeNode('array');

        $alias = new TypeAliasDoc('Row', $type, null);

        self::assertSame('Row', $alias->name);
        self::assertSame($type, $alias->type);
        self::assertNull($alias->importedFrom);
    }

    public function testStoresImportedAliasWithoutType(): void
    {
        $alias = new TypeAliasDoc('Row', null, 'App\\Acme\\Widget');

        self::assertSame('Row', $alias->name);
        self::assertNull($alias->type);
        self::assertSame('App\\Acme\\Widget', $alias->importedFrom);
    }
}
