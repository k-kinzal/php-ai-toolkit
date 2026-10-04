<?php

declare(strict_types=1);

namespace Tests\Unit\Guard\Policy;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Toolkit\Guard\Policy\Constraint
 * @uses \Toolkit\Guard\Document\Selection
 */
#[CoversClass(\Toolkit\Guard\Policy\Constraint::class)]
#[UsesClass(\Toolkit\Guard\Document\Selection::class)]
final class ConstraintTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testAcceptsOnlyTypedAllowedChoices(): void
    {
        $constraint = new \Toolkit\Guard\Policy\Constraint();
        self::assertTrue($constraint->accepts(new \Toolkit\Guard\Document\Selection(true, 'A'), ['one_of' => ['A', 'B']]));
        self::assertFalse($constraint->accepts(new \Toolkit\Guard\Document\Selection(true, 1), ['equals' => '1']));
        self::assertFalse($constraint->accepts(new \Toolkit\Guard\Document\Selection(false, null), ['equals' => null]));
    }

    /**
     * @throws JsonException
     */
    public function testCombinesNumericBounds(): void
    {
        $constraint = new \Toolkit\Guard\Policy\Constraint();
        self::assertTrue($constraint->accepts(new \Toolkit\Guard\Document\Selection(true, 1), ['min' => 1, 'max' => 2]));
        self::assertFalse($constraint->accepts(new \Toolkit\Guard\Document\Selection(true, 0), ['min' => 1]));
        self::assertFalse($constraint->accepts(new \Toolkit\Guard\Document\Selection(true, '1'), ['min' => 1]));
    }

    /**
     * @throws JsonException
     */
    public function testAcceptsOneRejectsUnknownOperators(): void
    {
        self::assertFalse((new \Toolkit\Guard\Policy\Constraint())->acceptsOne('equalz', 'A', 'A'));
    }
    /**
     * @throws JsonException
     */
    public function testOneOfUsesStrictTypes(): void
    {
        self::assertFalse((new \Toolkit\Guard\Policy\Constraint())->oneOf(1, ['1', true]));
    }
    /**
     * @throws JsonException
     */
    public function testEqualDistinguishesEmptyObjectsFromArrays(): void
    {
        self::assertFalse((new \Toolkit\Guard\Policy\Constraint())->equal((object) [], []));
    }
    public function testContainsMatchesNestedTextAndKeyPairs(): void
    {
        $constraint = new \Toolkit\Guard\Policy\Constraint();
        self::assertTrue($constraint->contains(['vendor/phpstan/phpstan-strict-rules/rules.neon'], 'phpstan/phpstan-strict-rules/rules.neon'));
        self::assertTrue($constraint->contains(['run' => "infection --min-msi=0\n"], '--min-msi=0'));
        self::assertTrue($constraint->contains(['MIN_MSI' => '80'], 'MIN_MSI=80'));
        self::assertFalse($constraint->contains('phpstan', ''));
    }

    public function testContainsAnyMatchesAnExactNameOrAPathFragment(): void
    {
        $constraint = new \Toolkit\Guard\Policy\Constraint();
        $includes = ['vendor/phpstan/phpstan-strict-rules/rules.neon', 'rules.neon'];
        self::assertTrue($constraint->containsAny($includes, ['k-kinzal/phpstan-guard-rules/rules.neon', 'rules.neon']));
        self::assertFalse($constraint->containsAny($includes, ['k-kinzal/phpstan-guard-rules/rules.neon']));
        self::assertFalse($constraint->containsAny('rules.neon', 'rules.neon'));
    }

    public function testNotContainsRejectsAPresentNeedle(): void
    {
        $constraint = new \Toolkit\Guard\Policy\Constraint();
        self::assertTrue($constraint->notContains(['@phpstan', '@compat'], 'php-fuzzer'));
        self::assertFalse($constraint->notContains('php-fuzzer fuzz', 'php-fuzzer'));
    }

    public function testMatchesTreatsABareNameAsExact(): void
    {
        $constraint = new \Toolkit\Guard\Policy\Constraint();
        self::assertTrue($constraint->matches(['rules.neon'], 'rules.neon'));
        self::assertFalse($constraint->matches(['vendor/phpstan/phpstan-strict-rules/rules.neon'], 'rules.neon'));
        self::assertTrue($constraint->matches(['vendor/k-kinzal/phpstan-guard-rules/rules.neon'], 'k-kinzal/phpstan-guard-rules/rules.neon'));
    }

    public function testPairReadsAStringMapEntry(): void
    {
        $constraint = new \Toolkit\Guard\Policy\Constraint();
        self::assertTrue($constraint->pair(['MIN_MSI' => '80'], 'MIN_MSI=80'));
        self::assertFalse($constraint->pair(['80'], 'MIN_MSI=80'));
        self::assertFalse($constraint->pair(['MIN_MSI' => 80], 'MIN_MSI=80'));
    }

    /**
     * @throws JsonException
     */
    public function testAcceptsAnAbsentFieldAndAPresentOne(): void
    {
        $constraint = new \Toolkit\Guard\Policy\Constraint();
        self::assertTrue($constraint->accepts(new \Toolkit\Guard\Document\Selection(false, null), ['absent' => true]));
        self::assertFalse($constraint->accepts(new \Toolkit\Guard\Document\Selection(true, 'pbt'), ['absent' => true]));
        self::assertTrue($constraint->accepts(new \Toolkit\Guard\Document\Selection(true, 'phpstan'), ['present' => true]));
    }

    public function testCanonicalIgnoresMapOrderAndRetainsListOrder(): void
    {
        $constraint = new \Toolkit\Guard\Policy\Constraint();
        self::assertSame('{"a":1,"b":2}', json_encode($constraint->canonical((object) ['b' => 2, 'a' => 1])));
        self::assertSame('[2,1]', json_encode($constraint->canonical([2, 1])));
    }
}
