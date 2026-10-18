<?php

declare(strict_types=1);

namespace Tests\Unit\Rule;

use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Rule\Template;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Rule\Template
 * @uses \DecisionsLint\Diagnostic\LintException
 */
#[CoversClass(Template::class)]
#[UsesClass(LintException::class)]
final class TemplateTest extends TestCase
{
    public function testPlaceholdersListsEachNameOnce(): void
    {
        self::assertSame(['name', 'class'], (new Template())->placeholders('Method {name}() of {class} is not part of {class}.'));
        self::assertSame([], (new Template())->placeholders('No placeholders.'));
    }

    public function testCheckAcceptsKnownPlaceholders(): void
    {
        $this->expectNotToPerformAssertions();
        (new Template())->check('{file}:{line}:{column} {kind} {name} {class} {namespace} {qualified_name} {signature} {visibility} {rule} {answer} {probability} {confidence} {choice} {score} {threshold}', 'rule.x');
    }

    public function testCheckRejectsAnUnknownPlaceholder(): void
    {
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('Rule "x": decision.instructions uses the unknown placeholder {method}. Use one of: {file}, {line}');
        (new Template())->check('Is {method} right?', 'Rule "x": decision.instructions');
    }

    public function testRenderReplacesKnownValuesAndKeepsTheRest(): void
    {
        self::assertSame('Method foo() of {class}.', (new Template())->render('Method {name}() of {class}.', ['name' => 'foo']));
    }
}
