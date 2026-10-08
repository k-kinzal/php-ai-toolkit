<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Architecture\Dependency;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Architecture\Dependency\DependencyPath;
use Toolkit\PhpStan\Rule\Architecture\Dependency\DependencyRestrictions;

/**
 * @covers \Toolkit\PhpStan\Rule\Architecture\Dependency\DependencyRestrictions
 * @uses \Toolkit\PhpStan\Rule\Architecture\Dependency\DependencyPath
 */
#[CoversClass(DependencyRestrictions::class)]
#[UsesClass(DependencyPath::class)]
final class DependencyRestrictionsTest extends TestCase
{
    public function testViolationDefaultsIsolateEveryRootDirectory(): void
    {
        $restrictions = new DependencyRestrictions(new DependencyPath('/project'));

        self::assertSame('*/**', $restrictions->violation('/project/tests/ClientTest.php', '/project/examples/Client.php'));
        self::assertSame('*/**', $restrictions->violation('/project/src/Client.php', '/project/example/input.json'));
        self::assertSame('*/**', $restrictions->violation('/project/example/demo.php', '/project/examples/Client.php'));
        self::assertSame('*/**', $restrictions->violation('/project/src/Client.php', '/project/tests/input.php'));
        self::assertSame('*/**', $restrictions->violation('/project/tests/ClientTest.php', '/project/fixtures/input.json'));
        self::assertSame('*/**', $restrictions->violation('/project/bench/run.php', '/project/tests/input.php'));
    }

    public function testViolationDefaultsAllowTheSameRootSourcesAndExternalLibraries(): void
    {
        $restrictions = new DependencyRestrictions(new DependencyPath('/project'));

        self::assertNull($restrictions->violation('/project/tests/ClientTest.php', '/project/src/Client.php'));
        self::assertNull($restrictions->violation('/project/src/Client.php', '/project/src/Other/Service.php'));
        self::assertNull($restrictions->violation('/project/tests/Unit/Test.php', '/project/tests/Integration/input.php'));
        self::assertNull($restrictions->violation('/project/bench/run.php', '/project/bench/data/input.json'));
        self::assertNull($restrictions->violation('/project/examples/run.php', '/project/src/Client.php'));
        self::assertNull($restrictions->violation('/project/src/Client.php', '/project/vendor/package/Client.php'));
        self::assertNull($restrictions->violation('/project/vendor/package/run.php', '/project/tests/input.php'));
        self::assertNull($restrictions->violation('/project/src/Client.php', '/project/composer.json'));
        self::assertNull($restrictions->violation('/project/bootstrap.php', '/project/tests/input.php'));
    }

    public function testViolationSupportsGenericDirectedPoliciesAndSourceExceptions(): void
    {
        $restrictions = new DependencyRestrictions(new DependencyPath('/project'), [
            ['from' => ['src/**'], 'excludeFrom' => ['src/Bridge/**'], 'to' => ['tests/**']],
        ]);

        self::assertSame('tests/**', $restrictions->violation('/project/src/Client.php', '/project/tests/input.php'));
        self::assertNull($restrictions->violation('/project/tests/input.php', '/project/src/Client.php'));
        self::assertNull($restrictions->violation('/project/src/Bridge/Client.php', '/project/tests/input.php'));
    }

    public function testChecksSkipsExemptSourcesAndEmptyPolicies(): void
    {
        self::assertTrue((new DependencyRestrictions(new DependencyPath('/project')))->checks('/project/tests/Test.php'));
        self::assertTrue((new DependencyRestrictions(new DependencyPath('/project')))->checks('/project/examples/Demo.php'));
        self::assertFalse((new DependencyRestrictions(new DependencyPath('/project')))->checks('/project/vendor/package/Demo.php'));
        self::assertFalse((new DependencyRestrictions(new DependencyPath('/project')))->checks('/project/bootstrap.php'));
        self::assertFalse((new DependencyRestrictions(new DependencyPath('/project'), []))->checks('/project/tests/Test.php'));
    }

    public function testViolationTargetExceptionsApplyOnlyToTheirOwnPolicy(): void
    {
        $restrictions = new DependencyRestrictions(new DependencyPath('/project'), [
            ['from' => ['tests/**'], 'to' => ['*/**'], 'excludeTo' => ['fixtures/**'], 'allowSameRootDirectory' => true],
            ['from' => ['tests/**'], 'to' => ['fixtures/private/**']],
        ]);

        self::assertNull($restrictions->violation('/project/tests/Test.php', '/project/fixtures/input.json'));
        self::assertNull($restrictions->violation('/project/tests/Unit/Test.php', '/project/tests/Support.php'));
        self::assertSame('fixtures/private/**', $restrictions->violation('/project/tests/Test.php', '/project/fixtures/private/input.json'));
        self::assertSame('*/**', $restrictions->violation('/project/tests/Test.php', '/project/examples/input.json'));
    }

    public function testViolationKeepsExplicitSameDirectoryBansUnlessOptedOut(): void
    {
        $restrictions = new DependencyRestrictions(new DependencyPath('/project'), [
            ['from' => ['src/Public/**'], 'to' => ['src/Internal/**']],
        ]);

        self::assertSame('src/Internal/**', $restrictions->violation('/project/src/Public/Client.php', '/project/src/Internal/Service.php'));
    }
}
