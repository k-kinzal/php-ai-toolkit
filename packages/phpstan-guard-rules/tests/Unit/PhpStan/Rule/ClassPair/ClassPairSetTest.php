<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ClassPair;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\ClassPair\AutoloadPlaceholderExpander;
use Toolkit\PhpStan\Rule\ClassPair\ClassNameGlob;
use Toolkit\PhpStan\Rule\ClassPair\ClassNamePattern;
use Toolkit\PhpStan\Rule\ClassPair\ClassPair;
use Toolkit\PhpStan\Rule\ClassPair\ClassPairSet;
use Toolkit\PhpStan\Rule\ClassPair\ComposerAutoloadMap;

/**
 * @covers \Toolkit\PhpStan\Rule\ClassPair\ClassPairSet
 * @uses \Toolkit\PhpStan\Rule\ClassPair\AutoloadPlaceholderExpander
 * @uses \Toolkit\PhpStan\Rule\ClassPair\ClassNameGlob
 * @uses \Toolkit\PhpStan\Rule\ClassPair\ClassNamePattern
 * @uses \Toolkit\PhpStan\Rule\ClassPair\ClassPair
 * @uses \Toolkit\PhpStan\Rule\ClassPair\ComposerAutoloadMap
 */
#[CoversClass(ClassPairSet::class)]
#[UsesClass(AutoloadPlaceholderExpander::class)]
#[UsesClass(ClassNameGlob::class)]
#[UsesClass(ClassNamePattern::class)]
#[UsesClass(ClassPair::class)]
#[UsesClass(ComposerAutoloadMap::class)]
final class ClassPairSetTest extends TestCase
{
    public function testPairsResolvePlaceholdersInTheShippedDefault(): void
    {
        $pairs = (new ClassPairSet(new ComposerAutoloadMap([['App', 'src'], ['Tests', 'tests']])))->pairs();

        self::assertCount(1, $pairs);
        self::assertSame('App\{name}', $pairs[0]->fromPattern());
        self::assertSame('Tests\Unit\{name}Test', $pairs[0]->toPattern());
        self::assertSame('Tests\Unit\Service\UserTest', $pairs[0]->counterpartOf('App\Service\User'));
    }

    public function testPairsSkipDefinitionsWithUnmappedPlaceholders(): void
    {
        $set = new ClassPairSet(new ComposerAutoloadMap([['App', 'src']]), [
            ['from' => '${src}\{name}', 'to' => '${tests/Unit}\{name}Test'],
            ['from' => '${lib}\{name}', 'to' => 'Tests\{name}Test'],
            ['from' => '${src}\{name}Interface', 'to' => '${src}\Fake\Fake{name}'],
        ]);

        self::assertCount(1, $set->pairs());
        self::assertSame('App\{name}Interface', $set->pairs()[0]->fromPattern());
    }

    public function testPairsApplyExpandedExcludePatterns(): void
    {
        $set = new ClassPairSet(new ComposerAutoloadMap([['App', 'src'], ['Tests', 'tests']]), [
            ['from' => '${src}\{name}', 'to' => '${tests/Unit}\{name}Test', 'exclude' => ['${src}\Generated\*', '${lib}\*', '*Interface']],
        ]);

        self::assertTrue($set->pairs()[0]->excludes('App\Generated\Entity'));
        self::assertTrue($set->pairs()[0]->excludes('App\MailerInterface'));
        self::assertFalse($set->pairs()[0]->excludes('App\Mailer'));
    }

    public function testPairsApplySharedExcludePatternsToEveryPair(): void
    {
        $set = new ClassPairSet(new ComposerAutoloadMap([['App', 'src'], ['Tests', 'tests']]), [
            ['from' => '${src}\{name}', 'to' => '${tests/Unit}\{name}Test'],
            ['from' => '${src}\{name}Interface', 'to' => '${src}\Fake\Fake{name}', 'exclude' => ['${src}\Legacy\*']],
        ], ['${tests/Unit}\Compatibility\AliasesTest']);

        self::assertTrue($set->pairs()[0]->excludes('Tests\Unit\Compatibility\AliasesTest'));
        self::assertTrue($set->pairs()[1]->excludes('Tests\Unit\Compatibility\AliasesTest'));
        self::assertTrue($set->pairs()[1]->excludes('App\Legacy\MailerInterface'));
        self::assertFalse($set->pairs()[0]->excludes('App\Legacy\MailerInterface'));
    }
}
