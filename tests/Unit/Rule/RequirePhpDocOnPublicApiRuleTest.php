<?php

declare(strict_types=1);

namespace Tests\Unit\Rule;

use PhpStanAiRules\Rule\RequirePhpDocOnPublicApiRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @extends RuleTestCase<RequirePhpDocOnPublicApiRule>
 */
#[CoversClass(RequirePhpDocOnPublicApiRule::class)]
final class RequirePhpDocOnPublicApiRuleTest extends RuleTestCase
{
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
        $this->analyse([__DIR__ . '/../../Fixture/RequirePhpDocOnPublicApi/MissingClassDoc.php'], [
            [
                'Class MissingClassDoc is missing a PHPDoc comment. Add a multi-line /** ... */ block describing its purpose.',
                7,
            ],
        ]);
    }

    public function testProcessNodePublicMethodWithoutPhpDocIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../Fixture/RequirePhpDocOnPublicApi/MissingMethodDoc.php'], [
            [
                'Public method MissingMethodDoc::undocumented() is missing a PHPDoc comment. Add a multi-line /** ... */ block describing what this method does, its parameters, and return value.',
                12,
            ],
            [
                'Public method MissingMethodDoc::__toString() is missing a PHPDoc comment. Add a multi-line /** ... */ block describing what this method does, its parameters, and return value.',
                16,
            ],
        ]);
    }

    public function testProcessNodePublicPropertyWithoutPhpDocIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../Fixture/RequirePhpDocOnPublicApi/MissingPropertyDoc.php'], [
            [
                'Public property MissingPropertyDoc::$undocumented is missing a PHPDoc comment. Add a multi-line /** ... */ block describing this property.',
                12,
            ],
        ]);
    }

    public function testProcessNodePublicConstantWithoutPhpDocIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../Fixture/RequirePhpDocOnPublicApi/MissingConstantDoc.php'], [
            [
                'Public constant MissingConstantDoc::UNDOCUMENTED is missing a PHPDoc comment. Add a multi-line /** ... */ block describing this constant.',
                12,
            ],
        ]);
    }

    public function testProcessNodeFullyDocumentedClassIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../Fixture/RequirePhpDocOnPublicApi/FullyDocumented.php'], []);
    }

    public function testProcessNodeNonPublicMembersAreNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../Fixture/RequirePhpDocOnPublicApi/NonPublicMembers.php'], []);
    }

    public function testProcessNodeInterfaceWithoutPhpDocIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../Fixture/RequirePhpDocOnPublicApi/InterfaceWithoutDoc.php'], [
            [
                'Interface InterfaceWithoutDoc is missing a PHPDoc comment. Add a multi-line /** ... */ block describing its purpose.',
                7,
            ],
            [
                'Public constant InterfaceWithoutDoc::STATUS is missing a PHPDoc comment. Add a multi-line /** ... */ block describing this constant.',
                9,
            ],
            [
                'Public method InterfaceWithoutDoc::doSomething() is missing a PHPDoc comment. Add a multi-line /** ... */ block describing what this method does, its parameters, and return value.',
                11,
            ],
        ]);
    }

    public function testProcessNodeTraitWithoutPhpDocIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../Fixture/RequirePhpDocOnPublicApi/TraitWithoutDoc.php'], [
            [
                'Trait TraitWithoutDoc is missing a PHPDoc comment. Add a multi-line /** ... */ block describing its purpose.',
                7,
            ],
            [
                'Public property TraitWithoutDoc::$name is missing a PHPDoc comment. Add a multi-line /** ... */ block describing this property.',
                9,
            ],
            [
                'Public method TraitWithoutDoc::doSomething() is missing a PHPDoc comment. Add a multi-line /** ... */ block describing what this method does, its parameters, and return value.',
                11,
            ],
        ]);
    }

    public function testProcessNodeEnumWithoutPhpDocIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../Fixture/RequirePhpDocOnPublicApi/EnumWithoutDoc.php'], [
            [
                'Enum EnumWithoutDoc is missing a PHPDoc comment. Add a multi-line /** ... */ block describing its purpose.',
                7,
            ],
        ]);
    }

    public function testProcessNodeClassInRestrictedTestNamespaceIsSkipped(): void
    {
        $this->analyse([__DIR__ . '/../../Fixture/RequirePhpDocOnPublicApi/TestClassInRestrictedNamespace.php'], []);
    }
}
