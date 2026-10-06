<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Profile;

use Guard\Config\Profile\PolicyConfig;
use Guard\Config\Profile\PolicyDefinition;
use Guard\Config\Profile\PolicyResolver;
use Guard\Config\Value\LimitConfig;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Profile\PolicyResolver
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\FileSet
 * @uses \Guard\Collect\Filesystem\Entry
 * @uses \Guard\Collect\Input
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Collect\Selection
 * @uses \Guard\Collect\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Profile\PolicyConfig
 * @uses \Guard\Config\Profile\PolicyDefinition
 * @uses \Guard\Config\Value\LimitConfig
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(PolicyResolver::class)]
#[UsesClass(\Guard\Collect\DirectoryListing::class)]
#[UsesClass(\Guard\Collect\FileRecord::class)]
#[UsesClass(\Guard\Collect\FileSet::class)]
#[UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[UsesClass(\Guard\Collect\Input::class)]
#[UsesClass(\Guard\Collect\InputSet::class)]
#[UsesClass(\Guard\Collect\Selection::class)]
#[UsesClass(\Guard\Collect\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(PolicyConfig::class)]
#[UsesClass(PolicyDefinition::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Extension\PolicyBinding::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class PolicyResolverTest extends TestCase
{
    public function testResolveAppliesInheritanceWithoutDeclarationOrder(): void
    {
        $policies = (new PolicyResolver())->resolve([
            'native' => new PolicyDefinition('native', 'standard', ['file.lines' => 900]),
            'standard' => new PolicyDefinition('standard', null, [
                'file.lines' => 500,
                'method.lines' => 50,
            ]),
        ]);

        self::assertSame(900, $policies['native']->limits->maxFileLines);
        self::assertSame(50, $policies['native']->limits->maxMethodLines);
    }

    public function testValidateParentsRejectsUnknownParent(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('extends unknown policy');

        (new PolicyResolver())->validateParents([
            'native' => new PolicyDefinition('native', 'missing', ['file.lines' => 900]),
        ]);
    }

    public function testResolveDefinitionRejectsPolicyWithNoEnabledLimits(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('must enable at least one');

        (new PolicyResolver())->resolveDefinition(
            new PolicyDefinition('empty', null, ['file.lines' => null]),
            [],
        );
    }
}
