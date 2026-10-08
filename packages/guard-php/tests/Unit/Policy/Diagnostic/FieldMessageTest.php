<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Diagnostic;

use Guard\Policy\Diagnostic\FieldMessage;
use Guard\Policy\Rule;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Diagnostic\FieldMessage
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Policy\Rule
 */
#[CoversClass(FieldMessage::class)]
#[UsesClass(Rule::class)]
#[UsesClass(\Guard\Diagnostic\PolicyException::class)]
final class FieldMessageTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testRenderUsesEffectiveValuesAndAnExplicitRepair(): void
    {
        $rule = new Rule('workers', 'app.json', 'json', '/workers', 'required', ['min' => 2, 'max' => 4], 3, true);
        self::assertSame('/workers in app.json must be at least 2 and be at most 4. Set /workers in app.json to 3. Run guard fix to apply the configured repair.', (new FieldMessage())->render($rule));
    }

    /**
     * @throws JsonException
     */
    public function testRenderTemplatesDoNotHardcodeAnImportedFileOrLimit(): void
    {
        $rule = new Rule('level', 'team.neon', 'neon', '/parameters/level', 'required', ['equals' => 8], 8, true, 'Analysis at {select} in {file} must {expectation}. {fix}');
        self::assertSame('Analysis at /parameters/level in team.neon must equal 8. Set /parameters/level in team.neon to 8. Run guard fix to apply the configured repair.', (new FieldMessage())->render($rule));
    }

    /**
     * @dataProvider providerAssertions
     * @throws JsonException
     */
    #[DataProvider('providerAssertions')]
    public function testExpectationAndStepExplainManualConstraints(string $kind, mixed $value, string $expectation, string $instruction): void
    {
        self::assertSame($expectation, (new FieldMessage())->expectation($kind, $value));
        self::assertSame($instruction, (new FieldMessage())->step($kind, $value, '/x in app.json5'));
    }

    /**
     * @return iterable<string, array{string, mixed, string, string}>
     */
    public static function providerAssertions(): iterable
    {
        yield 'minimum' => ['min', 2, 'be at least 2', 'Set /x in app.json5 to a number greater than or equal to 2.'];
        yield 'maximum' => ['max', 4, 'be at most 4', 'Set /x in app.json5 to a number less than or equal to 4.'];
        yield 'choice' => ['one_of', ['a', 'b'], 'be one of ["a","b"]', 'Set /x in app.json5 to one of ["a","b"].'];
        yield 'include' => ['contains', 'strict.neon', 'include "strict.neon"', 'Add "strict.neon" to /x in app.json5, preserving its other entries or text.'];
        yield 'include choice' => ['contains_any', ['a', 'b'], 'include at least one of ["a","b"]', 'Add at least one of ["a","b"] to /x in app.json5, preserving its other entries or text.'];
        yield 'exclude' => ['not_contains', 'skip', 'exclude "skip"', 'Remove "skip" from /x in app.json5, preserving unrelated content.'];
        yield 'missing' => ['present', true, 'exist', 'Add the missing field /x in app.json5.'];
        yield 'absent' => ['absent', true, 'be absent', 'Remove the field /x in app.json5.'];
    }

    /**
     * @throws JsonException
     */
    public function testFixNamesThePhpCsFixerApiForCheckOnlyRules(): void
    {
        $rules = new Rule('strict', 'style.php', 'php', '/rules/strict_comparison', 'required', ['equals' => true], true, false);
        $risky = new Rule('risky', 'style.php', 'php', '/riskyAllowed', 'required', ['equals' => true], true, false);
        self::assertSame('Set the strict_comparison option to true in the literal array passed to setRules() in style.php.', (new FieldMessage())->fix($rules));
        self::assertSame('Call setRiskyAllowed(true) in style.php.', (new FieldMessage())->fix($risky));
        self::assertStringNotContainsString('guard fix', (new FieldMessage())->render($rules));
    }

    /**
     * @throws JsonException
     */
    public function testQuoteDistinguishesScalarTypes(): void
    {
        self::assertSame('"true"', (new FieldMessage())->quote('true'));
        self::assertSame('true', (new FieldMessage())->quote(true));
        self::assertSame('1.0', (new FieldMessage())->quote(1.0));
    }
    /**
     * @throws JsonException
     */
    public function testStepRejectsUnsupportedAssertionsInsteadOfInventingARepair(): void
    {
        $this->expectException(\Guard\Diagnostic\PolicyException::class);
        (new FieldMessage())->step('unknown', null, 'app.json');
    }
}
