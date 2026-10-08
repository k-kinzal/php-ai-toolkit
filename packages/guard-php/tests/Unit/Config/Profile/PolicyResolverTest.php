<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Profile;

use Guard\Config\Profile\PolicyDefinition;
use Guard\Config\Profile\PolicyResolver;
use Guard\Diagnostic\PolicyException;
use Guard\Policy\Definition\LimitConfig;
use Guard\Policy\Definition\PolicyConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Profile\PolicyResolver
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Policy\Definition\PolicyConfig
 * @uses \Guard\Config\Profile\PolicyDefinition
 * @uses \Guard\Policy\Definition\LimitConfig
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Diagnostic\Finding
 */
#[CoversClass(PolicyResolver::class)]
#[UsesClass(\Guard\Input\DirectoryListing::class)]
#[UsesClass(\Guard\Input\FileRecord::class)]
#[UsesClass(\Guard\Input\FileSet::class)]
#[UsesClass(\Guard\Input\Entry::class)]
#[UsesClass(\Guard\Input\Input::class)]
#[UsesClass(\Guard\Input\InputSet::class)]
#[UsesClass(\Guard\Input\Selection::class)]
#[UsesClass(\Guard\Input\StructuredFile::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(PolicyConfig::class)]
#[UsesClass(PolicyDefinition::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(\Guard\Policy\Context::class)]
#[UsesClass(\Guard\Policy\FileChange::class)]
#[UsesClass(\Guard\Policy\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyBinding::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Diagnostic\Finding::class)]
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
