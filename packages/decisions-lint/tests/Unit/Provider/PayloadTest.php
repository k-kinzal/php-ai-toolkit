<?php

declare(strict_types=1);

namespace Tests\Unit\Provider;

use DecisionsLint\Diagnostic\LintException;
use DecisionsLint\Provider\Payload;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Provider\Payload
 * @uses \DecisionsLint\Diagnostic\LintException
 */
#[CoversClass(Payload::class)]
#[UsesClass(LintException::class)]
final class PayloadTest extends TestCase
{
    public function testFloatAcceptsIntegersAndFloats(): void
    {
        $payload = new Payload(['a' => 1, 'b' => 0.5], 'ctx');
        self::assertSame(1.0, $payload->float('a'));
        self::assertSame(0.5, $payload->float('b'));
    }

    public function testFloatRejectsAMissingField(): void
    {
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('Jev response.answers: field "noul" must be a number, but it is missing.');
        (new Payload([], 'Jev response.answers'))->float('noul');
    }

    public function testStringRejectsANonString(): void
    {
        self::assertSame('x', (new Payload(['s' => 'x'], 'ctx'))->string('s'));
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('field "s" must be a string, but it is 3');
        (new Payload(['s' => 3], 'ctx'))->string('s');
    }

    public function testStringOrFallsBackForAnAbsentOrNonStringValue(): void
    {
        self::assertSame('jev', (new Payload(['model' => 'jev'], 'ctx'))->stringOr('model', 'x'));
        self::assertSame('x', (new Payload(['model' => 1], 'ctx'))->stringOr('model', 'x'));
        self::assertSame('x', (new Payload([], 'ctx'))->stringOr('model', 'x'));
    }

    public function testNestedReadsAMappingWithAnExtendedContext(): void
    {
        $nested = (new Payload(['answers' => ['a' => ['noul' => 0.2]]], 'Jev response'))->nested('answers');
        self::assertSame(['a'], $nested->keys());
        self::assertSame(0.2, $nested->nested('a')->float('noul'));
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('Jev response.answers: field "b" must be a mapping or list, but it is missing');
        $nested->nested('b');
    }

    public function testFloatsReadsADistribution(): void
    {
        self::assertSame(['a' => 0.25, 'b' => 0.75], (new Payload(['p' => ['a' => 0.25, 'b' => 0.75]], 'ctx'))->floats('p'));
    }

    public function testEntriesReadsAListOfMappings(): void
    {
        $entries = (new Payload(['answers' => [['name' => 'x'], ['name' => 'y']]], 'ctx'))->entries('answers');
        self::assertCount(2, $entries);
        self::assertSame('y', $entries[1]->string('name'));
    }

    public function testKeysReturnsTheFieldNamesAsStrings(): void
    {
        self::assertSame(['0', 'a'], (new Payload([0 => 1, 'a' => 2], 'ctx'))->keys());
    }

    public function testHasTellsWhetherAFieldIsPresent(): void
    {
        $payload = new Payload(['present' => null], 'ctx');
        self::assertTrue($payload->has('present'));
        self::assertFalse($payload->has('absent'));
    }

    public function testProblemDescribesWhatWasFound(): void
    {
        $payload = new Payload([], 'OpenAI response');
        self::assertStringContainsString('field "x" must be a number, but it is array', $payload->problem('x', 'a number', []));
        self::assertStringContainsString('but it is \'abc\'', $payload->problem('x', 'a number', 'abc'));
        self::assertStringContainsString('report the response or update decisions-lint', $payload->problem('x', 'a number', null));
    }
}
