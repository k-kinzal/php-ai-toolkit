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
    public function testViolationDefaultsProtectBothExampleDirectoriesWithoutExemptingTests(): void
    {
        $restrictions = new DependencyRestrictions(new DependencyPath('/project'));

        self::assertSame('examples/**', $restrictions->violation('/project/tests/ClientTest.php', '/project/examples/Client.php'));
        self::assertSame('example/**', $restrictions->violation('/project/src/Client.php', '/project/example/input.json'));
        self::assertNull($restrictions->violation('/project/example/demo.php', '/project/examples/Client.php'));
        self::assertNull($restrictions->violation('/project/tests/ClientTest.php', '/project/src/Client.php'));
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
        self::assertFalse((new DependencyRestrictions(new DependencyPath('/project')))->checks('/project/examples/Demo.php'));
        self::assertFalse((new DependencyRestrictions(new DependencyPath('/project'), []))->checks('/project/tests/Test.php'));
    }
}
