<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Extension\RedundantDiagnostic;

use function array_filter;

use Override;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\Scope;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Large;
use PHPUnit\Framework\Attributes\UsesClass;
use Toolkit\PhpStan\Extension\RedundantDiagnostic\DirectThrowDiagnosticPolicy;
use Toolkit\PhpStan\Extension\RedundantDiagnostic\MemberDiagnosticPolicy;
use Toolkit\PhpStan\Extension\RedundantDiagnostic\RedundantDiagnosticErrorExtension;
use Toolkit\PhpStan\Extension\RedundantDiagnostic\RestrictedTestClassPolicy;
use Toolkit\PhpStan\Extension\RedundantDiagnostic\TestControlFlowDiagnosticPolicy;

/**
 * @covers \Toolkit\PhpStan\Extension\RedundantDiagnostic\RedundantDiagnosticErrorExtension
 * @uses \Toolkit\PhpStan\Extension\RedundantDiagnostic\DirectThrowDiagnosticPolicy
 * @uses \Toolkit\PhpStan\Extension\RedundantDiagnostic\MemberDiagnosticPolicy
 * @uses \Toolkit\PhpStan\Extension\RedundantDiagnostic\RestrictedTestClassPolicy
 * @uses \Toolkit\PhpStan\Extension\RedundantDiagnostic\TestControlFlowDiagnosticPolicy
 * @large
 */
#[CoversClass(RedundantDiagnosticErrorExtension::class)]
#[UsesClass(DirectThrowDiagnosticPolicy::class)]
#[UsesClass(MemberDiagnosticPolicy::class)]
#[UsesClass(RestrictedTestClassPolicy::class)]
#[UsesClass(TestControlFlowDiagnosticPolicy::class)]
#[Large]
final class RedundantDiagnosticErrorExtensionTest extends PHPStanTestCase
{
    /**
     * @return list<string>
     */
    #[Override]
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../../../../../rules.neon'];
    }

    public function testShouldIgnoreFollowsEnabledMemberRule(): void
    {
        $scope = self::createStub(Scope::class);
        $scope->method('getClassReflection')->willReturn(
            self::createReflectionProvider()->getClass('Tests\Unit\Fixture\TestClassScope\ClassInUnitNamespace')
        );
        $error = Error::decode([
            'message' => 'Method is unused.',
            'file' => __FILE__,
            'line' => 20,
            'canBeIgnored' => true,
            'filePath' => __FILE__,
            'traitFilePath' => null,
            'tip' => null,
            'nodeLine' => null,
            'nodeType' => null,
            'identifier' => 'method.unused',
            'metadata' => [],
            'fixedErrorDiffHash' => null,
            'fixedErrorDiffDiff' => null,
        ]);

        $enabled = new RedundantDiagnosticErrorExtension(
            ['Tests'],
            ['Tests\Unit', 'Tests\Integration'],
            true,
            true,
            true,
            true,
            true,
            true,
        );
        $disabled = new RedundantDiagnosticErrorExtension(
            ['Tests'],
            ['Tests\Unit', 'Tests\Integration'],
            false,
            false,
            false,
            false,
            false,
            false,
        );

        self::assertTrue($enabled->shouldIgnore($error, new \PhpParser\Node\Stmt\Nop(), $scope));
        self::assertFalse($disabled->shouldIgnore($error, new \PhpParser\Node\Stmt\Nop(), $scope));
    }

    public function testDistributedRulesRegisterTheExtension(): void
    {
        $extensions = self::getContainer()->getServicesByTag('phpstan.ignoreErrorExtension');

        self::assertNotEmpty(array_filter(
            $extensions,
            static fn ($extension): bool => $extension instanceof RedundantDiagnosticErrorExtension,
        ));
    }
}
