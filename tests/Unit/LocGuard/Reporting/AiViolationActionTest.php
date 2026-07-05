<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Reporting;

use PhpAiToolkit\LocGuard\Analysis\Violation;
use PhpAiToolkit\LocGuard\Reporting\AiViolationAction;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AiViolationAction::class)]
#[UsesClass(Violation::class)]
final class AiViolationActionTest extends TestCase
{
    public function testActionReturnsRuleSpecificMessage(): void
    {
        self::assertStringContainsString('Reduce branch count', (new AiViolationAction())->action(
            new Violation('src/A.php', 1, 'cyclomatic_complexity', 21, 20, 'Complex.'),
        ));
    }
}
