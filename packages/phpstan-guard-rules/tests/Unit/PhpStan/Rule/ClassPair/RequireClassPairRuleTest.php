<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ClassPair;

use Override;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;
use Toolkit\PhpStan\Rule\ClassPair\AutoloadPlaceholderExpander;
use Toolkit\PhpStan\Rule\ClassPair\ClassNameGlob;
use Toolkit\PhpStan\Rule\ClassPair\ClassNamePattern;
use Toolkit\PhpStan\Rule\ClassPair\ClassPair;
use Toolkit\PhpStan\Rule\ClassPair\ClassPairErrorBuilder;
use Toolkit\PhpStan\Rule\ClassPair\ClassPairSet;
use Toolkit\PhpStan\Rule\ClassPair\ComposerAutoloadMap;
use Toolkit\PhpStan\Rule\ClassPair\RequireClassPairRule;

/**
 * @extends RuleTestCase<RequireClassPairRule>
 * @covers \Toolkit\PhpStan\Rule\ClassPair\RequireClassPairRule
 * @uses \Toolkit\PhpStan\Rule\ClassPair\AutoloadPlaceholderExpander
 * @uses \Toolkit\PhpStan\Rule\ClassPair\ClassNameGlob
 * @uses \Toolkit\PhpStan\Rule\ClassPair\ClassNamePattern
 * @uses \Toolkit\PhpStan\Rule\ClassPair\ClassPair
 * @uses \Toolkit\PhpStan\Rule\ClassPair\ClassPairErrorBuilder
 * @uses \Toolkit\PhpStan\Rule\ClassPair\ClassPairSet
 * @uses \Toolkit\PhpStan\Rule\ClassPair\ComposerAutoloadMap
 * @medium
 */
#[CoversClass(RequireClassPairRule::class)]
#[UsesClass(AutoloadPlaceholderExpander::class)]
#[UsesClass(ClassNameGlob::class)]
#[UsesClass(ClassNamePattern::class)]
#[UsesClass(ClassPair::class)]
#[UsesClass(ClassPairErrorBuilder::class)]
#[UsesClass(ClassPairSet::class)]
#[UsesClass(ComposerAutoloadMap::class)]
#[Medium]
final class RequireClassPairRuleTest extends RuleTestCase
{
    /**
     * @return list<string>
     */
    #[Override]
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../../../../../rules.neon'];
    }

    #[Override]
    protected function getRule(): Rule
    {
        return new RequireClassPairRule(
            self::createReflectionProvider(),
            __DIR__ . '/../../../../../tests/PhpStan/ClassPair/Project',
            [['from' => '${src}\{name}', 'to' => '${tests/Unit}\{name}Test', 'exclude' => ['${src}\Skipped']]],
        );
    }

    public function testGetNodeTypeInspectsClassDeclarations(): void
    {
        self::assertSame(InClassNode::class, $this->getRule()->getNodeType());
    }

    public function testDistributedRulesEnableClassPairsByDefault(): void
    {
        self::assertNotEmpty(array_filter(
            self::getContainer()->getServicesByTag('phpstan.rules.rule'),
            static fn ($rule): bool => $rule instanceof RequireClassPairRule,
        ));
    }

    public function testProcessNodeReportsClassWithoutCounterpart(): void
    {
        $this->analyse([__DIR__ . '/../../../../../tests/PhpStan/ClassPair/Project/src/Unpaired.php'], [
            [
                'Create class "Tests\PhpStan\ClassPair\Project\tests\Unit\UnpairedTest" in tests/Unit/UnpairedTest.php for class "Tests\PhpStan\ClassPair\Project\src\Unpaired": every class matching "Tests\PhpStan\ClassPair\Project\src\{name}" needs a class matching "Tests\PhpStan\ClassPair\Project\tests\Unit\{name}Test".',
                7,
            ],
        ]);
    }

    public function testProcessNodeAcceptsClassWithCounterpart(): void
    {
        $this->analyse([
            __DIR__ . '/../../../../../tests/PhpStan/ClassPair/Project/src/Paired.php',
            __DIR__ . '/../../../../../tests/PhpStan/ClassPair/Project/src/Nested/Deep.php',
            __DIR__ . '/../../../../../tests/PhpStan/ClassPair/Project/tests/Unit/PairedTest.php',
            __DIR__ . '/../../../../../tests/PhpStan/ClassPair/Project/tests/Unit/Nested/DeepTest.php',
        ], []);
    }

    public function testProcessNodeIgnoresFilesWithoutClassDeclarations(): void
    {
        $this->analyse([__DIR__ . '/../../../../../tests/PhpStan/ClassPair/Project/src/functions.php'], []);
    }

    public function testProcessNodeReportsCounterpartWithoutOrigin(): void
    {
        $this->analyse([__DIR__ . '/../../../../../tests/PhpStan/ClassPair/Project/tests/Unit/OrphanedTest.php'], [
            [
                'Create class "Tests\PhpStan\ClassPair\Project\src\Orphaned" in src/Orphaned.php for class "Tests\PhpStan\ClassPair\Project\tests\Unit\OrphanedTest", or remove "Tests\PhpStan\ClassPair\Project\tests\Unit\OrphanedTest": every class matching "Tests\PhpStan\ClassPair\Project\tests\Unit\{name}Test" needs a class matching "Tests\PhpStan\ClassPair\Project\src\{name}".',
                7,
            ],
        ]);
    }

    public function testProcessNodeSkipsExcludedClasses(): void
    {
        $this->analyse([__DIR__ . '/../../../../../tests/PhpStan/ClassPair/Project/src/Skipped.php'], []);
    }
}
