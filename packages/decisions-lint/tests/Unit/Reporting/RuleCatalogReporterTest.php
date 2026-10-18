<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use DecisionsLint\Reporting\RuleCatalogReporter;
use DecisionsLint\Rule\DecisionSpec;
use DecisionsLint\Rule\Rule;
use DecisionsLint\Rule\TargetSelector;
use DecisionsLint\Rule\Violation;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Reporting\RuleCatalogReporter
 * @uses \DecisionsLint\Rule\DecisionSpec
 * @uses \DecisionsLint\Rule\Rule
 * @uses \DecisionsLint\Rule\TargetSelector
 * @uses \DecisionsLint\Rule\Violation
 */
#[CoversClass(RuleCatalogReporter::class)]
#[UsesClass(DecisionSpec::class)]
#[UsesClass(Rule::class)]
#[UsesClass(TargetSelector::class)]
#[UsesClass(Violation::class)]
final class RuleCatalogReporterTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testRenderListsRulesAsTextOrJson(): void
    {
        $rules = [
            new Rule('a.one', 'First rule.', 'warning', true, ['src/**'], [], new TargetSelector('method', ['public']), 'class', new DecisionSpec('predicate', 'Q?', [], [], 0.7), [new Violation(false, null, null, 'm', 'f')]),
            new Rule('b.two', '', 'error', false, [], [], new TargetSelector('file'), 'file', new DecisionSpec('score', 'Rate.', [], ['x', 'y']), [new Violation(null, 0.5, null, 'm', 'f')]),
        ];
        $reporter = new RuleCatalogReporter();
        self::assertSame("a.one  warning  method predicate  enabled  src/**\n  First rule.\nb.two  error  file score  disabled\n2 rules.\n", $reporter->render($rules, 'text'));
        $json = $reporter->render($rules, 'json');
        self::assertStringContainsString('"count": 2', $json);
        self::assertStringContainsString('"id": "a.one"', $json);
        self::assertStringContainsString('"threshold": 0.7', $json);
        self::assertStringContainsString('"min_score": 0.5', $json);
        self::assertSame("0 rules.\n", $reporter->render([], 'text'));
    }

    public function testRowFlattensTheRule(): void
    {
        $row = (new RuleCatalogReporter())->row(new Rule('a', 'D', 'info', true, [], ['x'], new TargetSelector('property', [], ['*Id'], ['legacy*'], true), 'declaration', new DecisionSpec('choice', 'Pick.', ['p' => 'P', 'q' => 'Q']), [new Violation('q', null, null, 'm', 'f')]));
        self::assertSame(['kind' => 'property', 'visibility' => [], 'names' => ['*Id'], 'exclude_names' => ['legacy*'], 'static' => true], $row['target']);
        self::assertSame(['type' => 'choice', 'instructions' => 'Pick.', 'choices' => ['p' => 'P', 'q' => 'Q'], 'levels' => [], 'threshold' => 0.5], $row['decision']);
        self::assertSame([['answer' => 'q', 'min_score' => null, 'max_score' => null, 'message' => 'm', 'fix' => 'f']], $row['violations']);
        self::assertSame(['x'], $row['exclude']);
    }
}
