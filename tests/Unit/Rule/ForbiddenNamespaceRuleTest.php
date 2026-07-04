<?php

declare(strict_types=1);

namespace Tests\Unit\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PhpStanAiRules\Rule\ForbiddenNamespaceRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

/**
 * @extends RuleTestCase<ForbiddenNamespaceRule>
 */
#[CoversClass(ForbiddenNamespaceRule::class)]
#[Medium]
final class ForbiddenNamespaceRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ForbiddenNamespaceRule([
            'Tests\\Support',
            'Tests\\Supports',
            'Tests\\Helper',
            'Tests\\Helpers',
            'Tests\\Util',
            'Tests\\Utils',
            'Tests\\Utility',
            'Tests\\Utilities',
        ]);
    }

    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PhpParser\Node\Stmt\Namespace_::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeForbiddenNamespacesAreReported(): void
    {
        $this->analyse([__DIR__ . '/../../Fixture/ForbiddenNamespace/WithForbiddenNamespaces.php'], [
            [
                'Namespace "Tests\Support" is prohibited by forbidden prefix "Tests\Support". Do not create generic test support/helper/utility namespaces; use an existing library, create an independent internal library outside the Tests namespace, or accept duplication and write setup directly inside each test method.',
                5,
            ],
            [
                'Namespace "Tests\Supports\Fixture" is prohibited by forbidden prefix "Tests\Supports". Do not create generic test support/helper/utility namespaces; use an existing library, create an independent internal library outside the Tests namespace, or accept duplication and write setup directly inside each test method.',
                11,
            ],
            [
                'Namespace "Tests\Helper" is prohibited by forbidden prefix "Tests\Helper". Do not create generic test support/helper/utility namespaces; use an existing library, create an independent internal library outside the Tests namespace, or accept duplication and write setup directly inside each test method.',
                17,
            ],
            [
                'Namespace "Tests\Helpers\Fixture" is prohibited by forbidden prefix "Tests\Helpers". Do not create generic test support/helper/utility namespaces; use an existing library, create an independent internal library outside the Tests namespace, or accept duplication and write setup directly inside each test method.',
                23,
            ],
            [
                'Namespace "Tests\Util" is prohibited by forbidden prefix "Tests\Util". Do not create generic test support/helper/utility namespaces; use an existing library, create an independent internal library outside the Tests namespace, or accept duplication and write setup directly inside each test method.',
                29,
            ],
            [
                'Namespace "Tests\Utils\Fixture" is prohibited by forbidden prefix "Tests\Utils". Do not create generic test support/helper/utility namespaces; use an existing library, create an independent internal library outside the Tests namespace, or accept duplication and write setup directly inside each test method.',
                35,
            ],
            [
                'Namespace "Tests\Utility" is prohibited by forbidden prefix "Tests\Utility". Do not create generic test support/helper/utility namespaces; use an existing library, create an independent internal library outside the Tests namespace, or accept duplication and write setup directly inside each test method.',
                41,
            ],
            [
                'Namespace "Tests\Utilities\Fixture" is prohibited by forbidden prefix "Tests\Utilities". Do not create generic test support/helper/utility namespaces; use an existing library, create an independent internal library outside the Tests namespace, or accept duplication and write setup directly inside each test method.',
                47,
            ],
        ]);
    }

    public function testProcessNodeAllowedNamespacesAreNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../Fixture/ForbiddenNamespace/WithoutForbiddenNamespaces.php'], []);
    }
}

/**
 * @extends RuleTestCase<ForbiddenNamespaceRule>
 */
#[CoversClass(ForbiddenNamespaceRule::class)]
#[Medium]
final class ForbiddenNamespaceRuleWithoutConfiguredPrefixesTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ForbiddenNamespaceRule();
    }

    public function testProcessNodeWithoutConfiguredPrefixesDoesNotReport(): void
    {
        $this->analyse([__DIR__ . '/../../Fixture/ForbiddenNamespace/WithForbiddenNamespaces.php'], []);
    }
}
