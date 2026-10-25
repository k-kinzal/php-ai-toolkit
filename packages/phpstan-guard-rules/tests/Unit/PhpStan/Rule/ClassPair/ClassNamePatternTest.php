<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ClassPair;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\ClassPair\ClassNamePattern;

/**
 * @covers \Toolkit\PhpStan\Rule\ClassPair\ClassNamePattern
 */
#[CoversClass(ClassNamePattern::class)]
final class ClassNamePatternTest extends TestCase
{
    public function testPatternReturnsTheConfiguredTemplate(): void
    {
        self::assertSame('Tests\Unit\{name}Test', (new ClassNamePattern('Tests\Unit\{name}Test'))->pattern());
    }

    /**
     * @return iterable<string, array{string, string, string|null}>
     */
    public static function providerCapture(): iterable
    {
        yield 'prefix only' => ['App\{name}', 'App\Service\User', 'Service\User'];
        yield 'prefix and suffix' => ['Tests\Unit\{name}Test', 'Tests\Unit\Service\UserTest', 'Service\User'];
        yield 'suffix inside name' => ['Tests\Unit\{name}Test', 'Tests\Unit\TestCaseTest', 'TestCase'];
        yield 'prefix and infix' => ['App\Fake\Fake{name}', 'App\Fake\FakeMailer', 'Mailer'];
        yield 'namespace without placeholder' => ['App', 'App\Service\User', 'Service\User'];
        yield 'namespace with trailing separator' => ['App\\', 'App\User', 'User'];
        yield 'other namespace' => ['App\{name}', 'Lib\Service\User', null];
        yield 'missing suffix' => ['Tests\Unit\{name}Test', 'Tests\Unit\Service\User', null];
        yield 'nothing left for the name' => ['Tests\Unit\{name}Test', 'Tests\Unit\Test', null];
        yield 'name ending with separator' => ['Tests\Unit\{name}Test', 'Tests\Unit\Service\Test', null];
        yield 'prefix is the class itself' => ['App\{name}', 'App', null];
    }

    /**
     * @dataProvider providerCapture
     */
    #[DataProvider('providerCapture')]
    public function testCaptureReturnsTheTextStandingForName(string $pattern, string $class, ?string $expected): void
    {
        self::assertSame($expected, (new ClassNamePattern($pattern))->capture($class));
    }

    public function testExpandSubstitutesTheName(): void
    {
        self::assertSame('Tests\Unit\Service\UserTest', (new ClassNamePattern('Tests\Unit\{name}Test'))->expand('Service\User'));
        self::assertSame('App\Service\User', (new ClassNamePattern('App'))->expand('Service\User'));
    }
}
