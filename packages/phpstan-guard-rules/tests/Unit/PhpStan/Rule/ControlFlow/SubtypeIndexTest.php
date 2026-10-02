<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ControlFlow;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\ControlFlow\SubtypeIndex;

/**
 * @covers \Toolkit\PhpStan\Rule\ControlFlow\SubtypeIndex
 */
#[CoversClass(SubtypeIndex::class)]
final class SubtypeIndexTest extends TestCase
{
    public function testInstantiableUnderNamesTheClassesBelowARoot(): void
    {
        $index = new SubtypeIndex([
            ['name' => 'App\\Visa', 'instantiable' => true, 'ancestors' => ['App\\Visa', 'App\\Card', 'App\\Payment']],
            ['name' => 'App\\Transfer', 'instantiable' => true, 'ancestors' => ['App\\Transfer', 'App\\Payment']],
        ]);

        self::assertSame(['App\\Transfer', 'App\\Visa'], $index->instantiableUnder(['App\\Payment']));
    }

    public function testInstantiableUnderSkipsWhatCannotBeInstantiated(): void
    {
        $index = new SubtypeIndex([
            ['name' => 'App\\Card', 'instantiable' => false, 'ancestors' => ['App\\Card', 'App\\Payment']],
            ['name' => 'App\\Visa', 'instantiable' => true, 'ancestors' => ['App\\Visa', 'App\\Card', 'App\\Payment']],
        ]);

        self::assertSame(['App\\Visa'], $index->instantiableUnder(['App\\Payment']));
    }

    public function testInstantiableUnderNarrowsToTheNamedRoot(): void
    {
        $index = new SubtypeIndex([
            ['name' => 'App\\Visa', 'instantiable' => true, 'ancestors' => ['App\\Visa', 'App\\Card', 'App\\Payment']],
            ['name' => 'App\\Transfer', 'instantiable' => true, 'ancestors' => ['App\\Transfer', 'App\\Payment']],
        ]);

        self::assertSame(['App\\Visa'], $index->instantiableUnder(['App\\Card']));
    }

    public function testInstantiableUnderMergesSeveralRootsWithoutRepeating(): void
    {
        $index = new SubtypeIndex([
            ['name' => 'App\\Visa', 'instantiable' => true, 'ancestors' => ['App\\Visa', 'App\\Card', 'App\\Payment']],
            ['name' => 'App\\Transfer', 'instantiable' => true, 'ancestors' => ['App\\Transfer', 'App\\Payment']],
        ]);

        self::assertSame(['App\\Transfer', 'App\\Visa'], $index->instantiableUnder(['App\\Card', 'App\\Payment']));
    }

    public function testInstantiableUnderRecordsEachClassOnce(): void
    {
        $index = new SubtypeIndex([
            ['name' => 'App\\Visa', 'instantiable' => true, 'ancestors' => ['App\\Visa', 'App\\Payment']],
            ['name' => 'App\\Visa', 'instantiable' => true, 'ancestors' => ['App\\Visa', 'App\\Payment']],
        ]);

        self::assertSame(['App\\Visa'], $index->instantiableUnder(['App\\Payment']));
    }

    public function testInstantiableUnderAnswersNothingForAnUnknownRoot(): void
    {
        $index = new SubtypeIndex([
            ['name' => 'App\\Visa', 'instantiable' => true, 'ancestors' => ['App\\Visa', 'App\\Payment']],
        ]);

        self::assertSame([], $index->instantiableUnder(['App\\Absent']));
    }
}
