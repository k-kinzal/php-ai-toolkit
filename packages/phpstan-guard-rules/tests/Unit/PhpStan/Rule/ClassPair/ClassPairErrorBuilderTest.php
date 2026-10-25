<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ClassPair;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\ClassPair\ClassNameGlob;
use Toolkit\PhpStan\Rule\ClassPair\ClassNamePattern;
use Toolkit\PhpStan\Rule\ClassPair\ClassPair;
use Toolkit\PhpStan\Rule\ClassPair\ClassPairErrorBuilder;
use Toolkit\PhpStan\Rule\ClassPair\ComposerAutoloadMap;

/**
 * @covers \Toolkit\PhpStan\Rule\ClassPair\ClassPairErrorBuilder
 * @uses \Toolkit\PhpStan\Rule\ClassPair\ClassNameGlob
 * @uses \Toolkit\PhpStan\Rule\ClassPair\ClassNamePattern
 * @uses \Toolkit\PhpStan\Rule\ClassPair\ClassPair
 * @uses \Toolkit\PhpStan\Rule\ClassPair\ComposerAutoloadMap
 */
#[CoversClass(ClassPairErrorBuilder::class)]
#[UsesClass(ClassNameGlob::class)]
#[UsesClass(ClassNamePattern::class)]
#[UsesClass(ClassPair::class)]
#[UsesClass(ComposerAutoloadMap::class)]
final class ClassPairErrorBuilderTest extends TestCase
{
    public function testMissingNamesTheCounterpartItsFileAndThePair(): void
    {
        $builder = new ClassPairErrorBuilder(new ComposerAutoloadMap([['App', 'src'], ['Tests', 'tests']]));
        $pair = new ClassPair(new ClassNamePattern('App\{name}'), new ClassNamePattern('Tests\Unit\{name}Test'), new ClassNameGlob([]));

        $error = $builder->missing('App\Service\User', 'Tests\Unit\Service\UserTest', $pair, 7);

        self::assertSame('customRules.classPairMissing', $error->getIdentifier());
        self::assertSame(
            'Create class "Tests\Unit\Service\UserTest" in tests/Unit/Service/UserTest.php for class "App\Service\User": every class matching "App\{name}" needs a class matching "Tests\Unit\{name}Test".',
            $error->getMessage(),
        );
    }

    public function testOrphanedOffersRemovingTheCounterpart(): void
    {
        $builder = new ClassPairErrorBuilder(new ComposerAutoloadMap([['App', 'src'], ['Tests', 'tests']]));
        $pair = new ClassPair(new ClassNamePattern('App\{name}'), new ClassNamePattern('Tests\Unit\{name}Test'), new ClassNameGlob([]));

        $error = $builder->orphaned('Tests\Unit\Service\LegacyTest', 'App\Service\Legacy', $pair, 9);

        self::assertSame('customRules.classPairOrphaned', $error->getIdentifier());
        self::assertSame(
            'Create class "App\Service\Legacy" in src/Service/Legacy.php for class "Tests\Unit\Service\LegacyTest", or remove "Tests\Unit\Service\LegacyTest": every class matching "Tests\Unit\{name}Test" needs a class matching "App\{name}".',
            $error->getMessage(),
        );
    }

    public function testLocationIsEmptyWhenNoAutoloadEntryCoversTheClass(): void
    {
        $builder = new ClassPairErrorBuilder(new ComposerAutoloadMap([['App', 'src']]));

        self::assertSame(' in src/User.php', $builder->location('App\User'));
        self::assertSame('', $builder->location('Lib\User'));
    }
}
