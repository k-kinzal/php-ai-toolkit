<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use Override;
use PhpAiToolkit\PhpStan\Rule\RequirePhpDocOnPublicApiRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

/**
 * @extends RuleTestCase<RequirePhpDocOnPublicApiRule>
 */
#[CoversClass(RequirePhpDocOnPublicApiRule::class)]
#[Medium]
final class RequirePhpDocOnPublicApiRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new RequirePhpDocOnPublicApiRule();
    }

    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PhpParser\Node\Stmt\ClassLike::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeClassWithoutPhpDocIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/RequirePhpDocOnPublicApi/MissingClassDoc.php'], [
            [
                'Add a multi-line PHPDoc block to class MissingClassDoc describing its purpose.',
                7,
            ],
        ]);
    }

    public function testProcessNodePublicMethodWithoutPhpDocIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/RequirePhpDocOnPublicApi/MissingMethodDoc.php'], [
            [
                'Add a multi-line PHPDoc block to public method MissingMethodDoc::undocumented() describing behavior, parameters, and return value.',
                12,
            ],
            [
                'Add a multi-line PHPDoc block to public method MissingMethodDoc::__toString() describing behavior, parameters, and return value.',
                16,
            ],
        ]);
    }

    public function testProcessNodePublicPropertyWithoutPhpDocIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/RequirePhpDocOnPublicApi/MissingPropertyDoc.php'], [
            [
                'Add a multi-line PHPDoc block to public property MissingPropertyDoc::$undocumented describing the property.',
                12,
            ],
        ]);
    }

    public function testProcessNodePublicConstantWithoutPhpDocIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/RequirePhpDocOnPublicApi/MissingConstantDoc.php'], [
            [
                'Add a multi-line PHPDoc block to public constant MissingConstantDoc::UNDOCUMENTED describing the constant.',
                12,
            ],
        ]);
    }

    public function testProcessNodeFullyDocumentedClassIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/RequirePhpDocOnPublicApi/FullyDocumented.php'], []);
    }

    public function testProcessNodeNonPublicMembersAreNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/RequirePhpDocOnPublicApi/NonPublicMembers.php'], []);
    }

    public function testProcessNodeInterfaceWithoutPhpDocIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/RequirePhpDocOnPublicApi/InterfaceWithoutDoc.php'], [
            [
                'Add a multi-line PHPDoc block to interface InterfaceWithoutDoc describing its purpose.',
                7,
            ],
            [
                'Add a multi-line PHPDoc block to public constant InterfaceWithoutDoc::STATUS describing the constant.',
                9,
            ],
            [
                'Add a multi-line PHPDoc block to public method InterfaceWithoutDoc::doSomething() describing behavior, parameters, and return value.',
                11,
            ],
        ]);
    }

    public function testProcessNodeTraitWithoutPhpDocIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/RequirePhpDocOnPublicApi/TraitWithoutDoc.php'], [
            [
                'Add a multi-line PHPDoc block to trait TraitWithoutDoc describing its purpose.',
                7,
            ],
            [
                'Add a multi-line PHPDoc block to public property TraitWithoutDoc::$name describing the property.',
                9,
            ],
            [
                'Add a multi-line PHPDoc block to public method TraitWithoutDoc::doSomething() describing behavior, parameters, and return value.',
                11,
            ],
        ]);
    }

    public function testProcessNodeEnumWithoutPhpDocIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/RequirePhpDocOnPublicApi/EnumWithoutDoc.php'], [
            [
                'Add a multi-line PHPDoc block to enum EnumWithoutDoc describing its purpose.',
                7,
            ],
        ]);
    }

    public function testProcessNodeClassInRestrictedTestNamespaceIsSkipped(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/RequirePhpDocOnPublicApi/TestClassInRestrictedNamespace.php'], []);
    }
}
