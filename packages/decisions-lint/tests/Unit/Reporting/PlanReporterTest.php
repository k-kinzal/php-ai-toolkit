<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use DecisionsLint\Decision\Inquiry;
use DecisionsLint\Lint\Assignment;
use DecisionsLint\Lint\Plan;
use DecisionsLint\Reporting\PlanReporter;
use DecisionsLint\Rule\DecisionSpec;
use DecisionsLint\Rule\Rule;
use DecisionsLint\Rule\TargetSelector;
use DecisionsLint\Source\Target;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Reporting\PlanReporter
 * @uses \DecisionsLint\Decision\Inquiry
 * @uses \DecisionsLint\Lint\Assignment
 * @uses \DecisionsLint\Lint\Plan
 * @uses \DecisionsLint\Rule\DecisionSpec
 * @uses \DecisionsLint\Rule\Rule
 * @uses \DecisionsLint\Rule\TargetSelector
 * @uses \DecisionsLint\Source\Target
 */
#[CoversClass(PlanReporter::class)]
#[UsesClass(Inquiry::class)]
#[UsesClass(Assignment::class)]
#[UsesClass(Plan::class)]
#[UsesClass(DecisionSpec::class)]
#[UsesClass(Rule::class)]
#[UsesClass(TargetSelector::class)]
#[UsesClass(Target::class)]
final class PlanReporterTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testRenderListsEachRequestWithItsRules(): void
    {
        $first = new Rule('a', '', 'error', true, [], [], new TargetSelector('method'), 'class', new DecisionSpec('predicate', 'Q?'), []);
        $second = new Rule('b', '', 'error', true, [], [], new TargetSelector('method'), 'class', new DecisionSpec('predicate', 'Q?'), []);
        $target = new Target('method', 'foo', 'App\A::foo', 'App', 'A', 'src/A.php', 12, 5, 'function foo()', 'public', [], 'D', 'C', 'F');
        $plan = new Plan(['src/A.php', 'src/B.php'], [new Inquiry([], [])], [new Assignment(0, $first, $target), new Assignment(0, $second, $target)]);
        $reporter = new PlanReporter();
        self::assertSame("src/A.php:12:5 method App\\A::foo (class) <- a, b\n2 files, 1 targets, 1 requests, 2 questions. Nothing was sent.\n", $reporter->render($plan, 'text'));
        self::assertSame([
            'requests' => [['file' => 'src/A.php', 'line' => 12, 'column' => 5, 'kind' => 'method', 'symbol' => 'App\A::foo', 'context' => 'class', 'rules' => ['a', 'b']]],
            'files' => 2,
            'targets' => 1,
            'questions' => 2,
        ], json_decode($reporter->render($plan, 'json'), true, 512, JSON_THROW_ON_ERROR));
    }
}
