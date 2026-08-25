<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Reporting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\ScopeGuard\Analysis\Violation;
use Toolkit\ScopeGuard\Reporting\AiViolationAction;

/**
 * @covers \Toolkit\ScopeGuard\Reporting\AiViolationAction
 * @uses \Toolkit\ScopeGuard\Analysis\Violation
 */
#[CoversClass(AiViolationAction::class)]
#[UsesClass(Violation::class)]
final class AiViolationActionTest extends TestCase
{
    public function testActionExplainsAnOutOfScopeReference(): void
    {
        $violation = new Violation('src/A.php', 1, 'out_of_scope', 'App\\A', 'A.');

        self::assertStringStartsWith('Move the referencing code', (new AiViolationAction())->action($violation));
    }

    public function testActionExplainsAnUnusableTag(): void
    {
        $violation = new Violation('src/A.php', 1, 'invalid_scope', 'App\\A', 'A.');

        self::assertStringStartsWith('Rewrite the @visibility tag', (new AiViolationAction())->action($violation));
    }

    public function testActionFallsBackForAnUnknownRule(): void
    {
        $violation = new Violation('src/A.php', 1, 'unknown', 'App\\A', 'A.');

        self::assertSame('Make the code satisfy the declared visibility scope.', (new AiViolationAction())->action($violation));
    }
}
