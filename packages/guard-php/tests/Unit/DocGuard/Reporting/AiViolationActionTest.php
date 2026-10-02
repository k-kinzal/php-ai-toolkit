<?php

declare(strict_types=1);

namespace Tests\Unit\DocGuard\Reporting;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGuard\Analysis\Violation;
use Toolkit\DocGuard\Reporting\AiViolationAction;

/**
 * @covers \Toolkit\DocGuard\Reporting\AiViolationAction
 * @uses \Toolkit\DocGuard\Analysis\Violation
 */
#[CoversClass(AiViolationAction::class)]
#[UsesClass(Violation::class)]
final class AiViolationActionTest extends TestCase
{
    public function testActionTellsAgentsToWriteInsideExistingSections(): void
    {
        $action = (new AiViolationAction())->action(new Violation('README.md', 3, 'unexpected_heading', null, '## Development', 'Added.'));

        self::assertStringStartsWith('Do not add a section.', $action);
        self::assertStringContainsString('inside the existing section', $action);
        self::assertStringContainsString('ask a human to update the DocGuard config', $action);
    }

    public function testActionCoversUndeclaredDocumentsAndUnknownRules(): void
    {
        self::assertStringStartsWith('Do not add a document.', (new AiViolationAction())->action(new Violation('docs/x.md', null, 'undeclared_document', null, null, 'Added.')));
        self::assertStringContainsString('ask a human', (new AiViolationAction())->action(new Violation('README.md', null, 'future_rule', null, null, 'Unknown.')));
    }
}
