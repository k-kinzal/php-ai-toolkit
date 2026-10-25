<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ClassPair;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\ClassPair\ClassNameGlob;

/**
 * @covers \Toolkit\PhpStan\Rule\ClassPair\ClassNameGlob
 */
#[CoversClass(ClassNameGlob::class)]
final class ClassNameGlobTest extends TestCase
{
    public function testMatchesWildcardsAcrossNamespaceSeparators(): void
    {
        $glob = new ClassNameGlob(['App\Generated\*', '*Interface', 'App\Exact', 'App\Enum?']);

        self::assertTrue($glob->matches('App\Generated\Deep\Model'));
        self::assertTrue($glob->matches('App\Service\MailerInterface'));
        self::assertTrue($glob->matches('App\Exact'));
        self::assertTrue($glob->matches('App\EnumA'));
        self::assertFalse($glob->matches('App\Exactly'));
        self::assertFalse($glob->matches('App\Enum'));
        self::assertFalse($glob->matches('App\Service\Mailer'));
    }

    public function testMatchesNothingWithoutPatterns(): void
    {
        self::assertFalse((new ClassNameGlob([]))->matches('App\Service\Mailer'));
    }
}
