<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\TestDependency;

use Guard\Policy\Rule;
use Guard\Policy\TestDependency\TestDependencyRules;
use Guard\Structure\Document\DataDocument;
use Guard\Structure\Document\DocumentNode;
use JsonException;
use Nette\Neon\Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\TestDependency\TestDependencyRules
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Structure\Document\DataDocument
 * @uses \Guard\Structure\Document\DocumentNode
 */
#[CoversClass(TestDependencyRules::class)]
#[UsesClass(Rule::class)]
#[UsesClass(DataDocument::class)]
#[UsesClass(DocumentNode::class)]
final class TestDependencyRulesTest extends TestCase
{
    public function testFilesIncludesComposerAndTheSelectedDeptracFile(): void
    {
        self::assertSame(['composer.json' => 'json', 'architecture.yml' => 'yaml'], (new TestDependencyRules('architecture.yml', ['Src']))->files());
    }

    public function testDirectoriesUsesDistinctNamespaces(): void
    {
        self::assertSame(['bench' => 'Bench', 'fuzz' => 'Fuzz', 'spec' => 'Spec', 'tests' => 'Tests'], (new TestDependencyRules('deptrac.yaml', ['Src']))->directories());
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function testRulesIgnoresMissingTestDirectories(): void
    {
        self::assertSame([], (new TestDependencyRules('deptrac.yaml', ['Src']))->rules('composer.json', ['src', 'vendor'], new DataDocument('json', '{}')));
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function testRulesMapsOnlyTheExistingDirectory(): void
    {
        $rules = (new TestDependencyRules('architecture.yml', ['Src']))->rules('composer.json', ['spec'], new DataDocument('json', '{}'));
        self::assertCount(2, $rules);
        self::assertSame('/autoload-dev/psr-4/Spec\\', $rules[0]->select);
        self::assertSame('spec/', $rules[0]->repair);
        self::assertSame('deptrac analyse --config-file=architecture.yml', $rules[1]->repair);
    }

    /**
     * @throws JsonException
     */
    public function testEqualProducesARequiredRepairWithAnActionableMessage(): void
    {
        $rule = (new TestDependencyRules('deptrac.yaml', ['Src']))->equal('composer.json', 'autoload.tests', '/autoload-dev/psr-4/Tests\\', 'tests/', 'Keep tests in their own namespace.');
        self::assertSame('required', $rule->level);
        self::assertTrue($rule->repairable);
        self::assertSame(['equals' => 'tests/'], $rule->assertions);
        self::assertStringContainsString('Keep tests in their own namespace.', $rule->message);
    }
}
