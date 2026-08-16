<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Diff;

use PhpAiToolkit\DocGen\Analysis\Diff\DiffKey;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DiffKey::class)]
final class DiffKeyTest extends TestCase
{
    public function testClassLikeKeysAreCaseInsensitiveAndLeadingSlashInsensitive(): void
    {
        $keys = new DiffKey();

        self::assertSame('c:demo\core\engine', $keys->classLike('Demo\Core\Engine'));
        self::assertSame($keys->classLike('Demo\Core\Engine'), $keys->classLike('\DEMO\CORE\ENGINE'));
    }

    public function testHeaderKeysAreSeparateFromSymbolKeys(): void
    {
        $keys = new DiffKey();

        self::assertSame('h:demo\core\engine', $keys->header('Demo\Core\Engine'));
        self::assertNotSame($keys->classLike('Demo\Core\Engine'), $keys->header('Demo\Core\Engine'));
    }

    public function testFunctionSymbolKeysAreCaseInsensitive(): void
    {
        $keys = new DiffKey();

        self::assertSame('f:demo\make', $keys->functionSymbol('Demo\make'));
        self::assertSame($keys->functionSymbol('Demo\make'), $keys->functionSymbol('\DEMO\MAKE'));
    }

    public function testMemberKeysCarryTheirKindAndKeepTheMemberNameAsWritten(): void
    {
        $keys = new DiffKey();

        self::assertSame('m:demo\engine::method.run', $keys->member('Demo\Engine', DiffKey::METHOD, 'run'));
        self::assertSame('m:demo\engine::constant.LIMIT', $keys->member('Demo\Engine', DiffKey::CONSTANT, 'LIMIT'));
        self::assertSame('m:demo\engine::property.count', $keys->member('Demo\Engine', DiffKey::PROPERTY, 'count'));
        self::assertSame('m:demo\status::case.Active', $keys->member('Demo\Status', DiffKey::ENUM_CASE, 'Active'));
    }

    public function testParameterKeysHangUnderTheirDeclaration(): void
    {
        $keys = new DiffKey();

        self::assertSame(
            'p:m:demo\engine::method.run#count',
            $keys->parameter($keys->member('Demo\Engine', DiffKey::METHOD, 'run'), 'count'),
        );
    }

    public function testReturnTypeKeysHangUnderTheirDeclaration(): void
    {
        $keys = new DiffKey();
        $owner = $keys->member('Demo\Engine', DiffKey::METHOD, 'run');

        self::assertSame('r:m:demo\engine::method.run', $keys->returnType($owner));
        self::assertNotSame($keys->returnType($owner), $keys->throwsTags($owner));
    }

    public function testThrowsTagsKeysHangUnderTheirDeclaration(): void
    {
        $keys = new DiffKey();

        self::assertSame('t:f:demo\greet', $keys->throwsTags($keys->functionSymbol('Demo\greet')));
    }

    public function testDocumentKeysNameThePackageAndThePath(): void
    {
        self::assertSame('d:demo/app/docs/guide.md', (new DiffKey())->document('demo/app', 'docs/guide.md'));
    }

    public function testPackageKeysNameTheComposerPackage(): void
    {
        $keys = new DiffKey();

        self::assertSame('k:demo/app', $keys->package('demo/app'));
        self::assertNotSame($keys->package('demo/app'), $keys->document('demo/app', ''));
    }

    public function testNamespaceNameKeysAreCaseInsensitiveWithinTheirPackage(): void
    {
        $keys = new DiffKey();

        self::assertSame('n:demo/app/demo\core', $keys->namespaceName('demo/app', 'Demo\Core'));
        self::assertSame($keys->namespaceName('demo/app', 'Demo\Core'), $keys->namespaceName('demo/app', 'DEMO\CORE'));
    }
}
