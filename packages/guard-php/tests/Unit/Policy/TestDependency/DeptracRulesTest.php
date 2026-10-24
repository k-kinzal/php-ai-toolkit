<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\TestDependency;

use Guard\Diagnostic\PolicyException;
use Guard\Policy\Rule;
use Guard\Policy\TestDependency\DeptracLayers;
use Guard\Policy\TestDependency\DeptracRules;
use Guard\Policy\TestDependency\TestDependencyRules;
use Guard\Structure\Document\DataDocument;
use Guard\Structure\Document\DocumentNode;
use Guard\Structure\Document\Pointer;
use Guard\Structure\Document\Selection;
use JsonException;
use Nette\Neon\Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\TestDependency\DeptracRules
 * @uses \Guard\Policy\TestDependency\TestDependencyRules
 * @uses \Guard\Policy\TestDependency\DeptracLayers
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Structure\Document\DataDocument
 * @uses \Guard\Structure\Document\DocumentNode
 * @uses \Guard\Structure\Document\Pointer
 * @uses \Guard\Structure\Document\Selection
 */
#[CoversClass(DeptracRules::class)]
#[UsesClass(TestDependencyRules::class)]
#[UsesClass(DeptracLayers::class)]
#[UsesClass(Rule::class)]
#[UsesClass(DataDocument::class)]
#[UsesClass(DocumentNode::class)]
#[UsesClass(Pointer::class)]
#[UsesClass(Selection::class)]
final class DeptracRulesTest extends TestCase
{
    /**
     * @throws JsonException
     * @throws Exception
     */
    public function testRulesRemoveReverseAndTransitiveTestEdges(): void
    {
        $document = new DataDocument('yaml', "deptrac:\n  ruleset:\n    Src: ['+Tests']\n    Tests: [Bench]\n");
        $rules = (new DeptracRules())->rules(new TestDependencyRules('deptrac.yaml', ['Src']), 'deptrac.yaml', ['tests' => 'Tests'], ['Src'], $document);
        $edges = $rules[3]->repair;
        self::assertEquals((object) ['Src' => [], 'Tests' => ['Src']], $edges);
    }

    /**
     * @throws JsonException
     * @throws Exception
     */
    public function testBypassesBlocksImportsAndRemovesTestBaselineExceptions(): void
    {
        $document = new DataDocument('yaml', "imports: [baseline.yaml]\ndeptrac:\n  exclude_files: ['.*Test.php']\n  skip_violations:\n    'Tests\\Case': ['App\\Thing']\n    'App\\Thing': ['Bench\\Case', 'App\\Legacy']\n");
        $rules = (new DeptracRules())->bypasses(new TestDependencyRules('deptrac.yaml', ['Src']), 'deptrac.yaml', $document);
        self::assertFalse($rules[0]->repairable);
        self::assertSame([], $rules[1]->repair);
        self::assertEquals((object) ['App\\Thing' => ['App\\Legacy']], $rules[2]->repair);
    }

    public function testTestNamespaceRequiresAnExactNamespaceSegment(): void
    {
        self::assertTrue((new DeptracRules())->testNamespace('\\Fuzz\\Target'));
        self::assertFalse((new DeptracRules())->testNamespace('Fuzzier\\Target'));
    }

    public function testSequenceRejectsMappingsInsteadOfDiscardingSettings(): void
    {
        $this->expectException(PolicyException::class);
        (new DeptracRules())->sequence((object) ['x' => 'src'], 'deptrac.paths');
    }
}
