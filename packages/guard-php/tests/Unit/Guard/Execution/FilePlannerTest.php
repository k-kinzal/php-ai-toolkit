<?php

declare(strict_types=1);

namespace Tests\Unit\Guard\Execution;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Toolkit\Guard\Execution\FilePlanner
 * @uses \Toolkit\Guard\Config\RuleReader
 * @uses \Toolkit\Guard\Config\Schema
 * @uses \Toolkit\Guard\Document\DataDocument
 * @uses \Toolkit\Guard\Document\Pointer
 * @uses \Toolkit\Guard\Document\Selection
 * @uses \Toolkit\Guard\Document\TomlEncoder
 * @uses \Toolkit\Guard\Document\XmlDocument
 * @uses \Toolkit\Guard\Execution\FileChange
 * @uses \Toolkit\Guard\Execution\Plan
 * @uses \Toolkit\Guard\Policy\Constraint
 * @uses \Toolkit\Guard\Policy\PolicyException
 * @uses \Toolkit\Guard\Policy\Rule
 * @uses \Toolkit\Guard\Policy\RuleEvaluator
 * @uses \Toolkit\Guard\Reporting\Finding
 */
#[CoversClass(\Toolkit\Guard\Execution\FilePlanner::class)]
#[UsesClass(\Toolkit\Guard\Config\RuleReader::class)]
#[UsesClass(\Toolkit\Guard\Config\Schema::class)]
#[UsesClass(\Toolkit\Guard\Document\DataDocument::class)]
#[UsesClass(\Toolkit\Guard\Document\Pointer::class)]
#[UsesClass(\Toolkit\Guard\Document\Selection::class)]
#[UsesClass(\Toolkit\Guard\Document\TomlEncoder::class)]
#[UsesClass(\Toolkit\Guard\Document\XmlDocument::class)]
#[UsesClass(\Toolkit\Guard\Execution\FileChange::class)]
#[UsesClass(\Toolkit\Guard\Execution\Plan::class)]
#[UsesClass(\Toolkit\Guard\Policy\Constraint::class)]
#[UsesClass(\Toolkit\Guard\Policy\PolicyException::class)]
#[UsesClass(\Toolkit\Guard\Policy\Rule::class)]
#[UsesClass(\Toolkit\Guard\Policy\RuleEvaluator::class)]
#[UsesClass(\Toolkit\Guard\Reporting\Finding::class)]
final class FilePlannerTest extends TestCase
{
    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testPlanRechecksConflictingRulesBeforeCommit(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'guard-plan-');
        self::assertIsString($path);
        file_put_contents($path, '{"mode":"C"}');
        $rules = (new \Toolkit\Guard\Config\RuleReader())->read([
            ['id' => 'a', 'file' => 'x.json', 'select' => '/mode', 'assert' => ['equals' => 'A']],
            ['id' => 'b', 'file' => 'x.json', 'select' => '/mode', 'assert' => ['equals' => 'B']],
        ]);
        self::assertNotEmpty($rules);
        $plan = (new \Toolkit\Guard\Execution\FilePlanner())->plan($path, $rules, true);
        self::assertCount(1, $plan->findings);
        self::assertSame('a', $plan->findings[0]->rule);
        self::assertSame('{"mode":"C"}', file_get_contents($path));
    }

    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testLeavesAlreadyCompliantFilesByteIdentical(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'guard-plan-');
        self::assertIsString($path);
        file_put_contents($path, "# Keep this comment\nmode: A\n");
        $rule = (new \Toolkit\Guard\Config\RuleReader())->rule(['id' => 'a', 'file' => 'x.yaml', 'select' => '/mode', 'assert' => ['one_of' => ['A', 'B']], 'repair' => 'B']);
        $plan = (new \Toolkit\Guard\Execution\FilePlanner())->plan($path, [$rule], true);
        self::assertSame([], $plan->changes);
        self::assertSame([], $plan->findings);
    }

}
