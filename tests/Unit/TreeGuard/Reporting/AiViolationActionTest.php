<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Reporting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\TreeGuard\Analysis\Violation;
use Toolkit\TreeGuard\Reporting\AiViolationAction;

/**
 * @covers \Toolkit\TreeGuard\Reporting\AiViolationAction
 * @uses \Toolkit\TreeGuard\Analysis\Violation
 */
#[CoversClass(AiViolationAction::class)]
#[UsesClass(Violation::class)]
final class AiViolationActionTest extends TestCase
{
    public function testActionSelectsRuleSpecificMessage(): void
    {
        $maxFiles = new Violation('src', 'max_files', 'src', 26, 25, 'M');
        $maxDepth = new Violation('src/A/B', 'max_depth', 'src', 4, 3, 'M');
        $denied = new Violation('src/AHelper.php', 'denied_file', 'src', null, null, 'M');
        $missing = new Violation('skills/x/SKILL.md', 'missing_required_file', 'skills/*', null, null, 'M');
        $empty = new Violation('src/Empty', 'empty_directory', 'src/**', null, null, 'M');
        $fileCase = new Violation('src/a.php', 'file_case', 'src/**', null, null, 'M');

        self::assertStringContainsString('direct file count', (new AiViolationAction())->action($maxFiles));
        self::assertStringContainsString('Flatten the hierarchy', (new AiViolationAction())->action($maxDepth));
        self::assertStringContainsString('denied pattern', (new AiViolationAction())->action($denied));
        self::assertStringContainsString('Create the required file', (new AiViolationAction())->action($missing));
        self::assertStringContainsString('Delete the empty directory', (new AiViolationAction())->action($empty));
        self::assertStringContainsString('naming convention', (new AiViolationAction())->action($fileCase));
    }

    public function testActionFallsBackForUnknownRule(): void
    {
        $violation = new Violation('src', 'future_rule', 'src', null, null, 'M');

        self::assertSame('Restructure the directory tree to satisfy the configured constraint.', (new AiViolationAction())->action($violation));
    }
}
