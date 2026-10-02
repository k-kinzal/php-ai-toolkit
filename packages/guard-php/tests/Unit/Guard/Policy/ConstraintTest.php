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
    public function testCanonicalIgnoresMapOrderAndRetainsListOrder(): void
    {
        $constraint = new \Toolkit\Guard\Policy\Constraint();
        self::assertSame('{"a":1,"b":2}', json_encode($constraint->canonical((object) ['b' => 2, 'a' => 1])));
        self::assertSame('[2,1]', json_encode($constraint->canonical([2, 1])));
    }
}
