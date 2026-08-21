<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Analysis\Reference;

use PhpAiToolkit\ScopeGuard\Analysis\Reference\Reference;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Reference::class)]
final class ReferenceTest extends TestCase
{
    /**
     * @dataProvider providerReference
     */
    #[DataProvider('providerReference')]
    public function testClassNameIsReadable(Reference $reference): void
    {
        self::assertSame('App\\Domain\\Order', $reference->className);
    }

    /**
     * @dataProvider providerReference
     */
    #[DataProvider('providerReference')]
    public function testMemberNameIsReadable(Reference $reference): void
    {
        self::assertSame('place', $reference->memberName);
    }

    /**
     * @dataProvider providerReference
     */
    #[DataProvider('providerReference')]
    public function testKindIsReadable(Reference $reference): void
    {
        self::assertSame('static call', $reference->kind);
    }

    /**
     * @dataProvider providerReference
     */
    #[DataProvider('providerReference')]
    public function testNamespaceIsReadable(Reference $reference): void
    {
        self::assertSame('App\\Http', $reference->namespace);
    }

    /**
     * @dataProvider providerReference
     */
    #[DataProvider('providerReference')]
    public function testPathIsReadable(Reference $reference): void
    {
        self::assertSame('src/Http/Controller.php', $reference->path);
    }

    /**
     * @dataProvider providerReference
     */
    #[DataProvider('providerReference')]
    public function testLineIsReadable(Reference $reference): void
    {
        self::assertSame(21, $reference->line);
    }


    /**
     * @return array<string, array{Reference}>
     */
    public static function providerReference(): array
    {
        return ['a static call reference' => [
            new Reference('App\\Domain\\Order', 'place', 'static call', 'App\\Http', 'src/Http/Controller.php', 21),
        ]];
    }
}
