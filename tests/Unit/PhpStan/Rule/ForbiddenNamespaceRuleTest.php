<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use Override;
use PhpAiToolkit\PhpStan\Rule\ForbiddenNamespacePrefixes;
use PhpAiToolkit\PhpStan\Rule\ForbiddenNamespaceRule;
use PhpAiToolkit\PhpStan\Rule\NamespacePrefixNormalizer;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

/**
 * @extends RuleTestCase<ForbiddenNamespaceRule>
 */
#[CoversClass(ForbiddenNamespaceRule::class)]
#[CoversClass(ForbiddenNamespacePrefixes::class)]
#[CoversClass(NamespacePrefixNormalizer::class)]
#[Medium]
final class ForbiddenNamespaceRuleTest extends RuleTestCase
{
    #[Override]
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
        $this->analyse([__DIR__ . '/../../../Fixture/ForbiddenNamespace/WithForbiddenNamespaces.php'], [
            [
                'Move code out of namespace "Tests\Support". Use a namespace outside forbidden test prefix "Tests\Support", or inline setup in each test.',
                5,
            ],
            [
                'Move code out of namespace "Tests\Supports\Fixture". Use a namespace outside forbidden test prefix "Tests\Supports", or inline setup in each test.',
                11,
            ],
            [
                'Move code out of namespace "Tests\Helper". Use a namespace outside forbidden test prefix "Tests\Helper", or inline setup in each test.',
                17,
            ],
            [
                'Move code out of namespace "Tests\Helpers\Fixture". Use a namespace outside forbidden test prefix "Tests\Helpers", or inline setup in each test.',
                23,
            ],
            [
                'Move code out of namespace "Tests\Util". Use a namespace outside forbidden test prefix "Tests\Util", or inline setup in each test.',
                29,
            ],
            [
                'Move code out of namespace "Tests\Utils\Fixture". Use a namespace outside forbidden test prefix "Tests\Utils", or inline setup in each test.',
                35,
            ],
            [
                'Move code out of namespace "Tests\Utility". Use a namespace outside forbidden test prefix "Tests\Utility", or inline setup in each test.',
                41,
            ],
            [
                'Move code out of namespace "Tests\Utilities\Fixture". Use a namespace outside forbidden test prefix "Tests\Utilities", or inline setup in each test.',
                47,
            ],
        ]);
    }

    public function testProcessNodeAllowedNamespacesAreNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbiddenNamespace/WithoutForbiddenNamespaces.php'], []);
    }

    public function testNormalizeConvertsSeparatorsAndTrimsNamespaceBoundaries(): void
    {
        self::assertSame('Tests\Support', (new NamespacePrefixNormalizer())->normalize('\\Tests/Support\\'));
    }

    public function testMatchingPrefixReturnsForbiddenPrefix(): void
    {
        $prefixes = new ForbiddenNamespacePrefixes(['Tests/Support']);

        self::assertSame('Tests\Support', $prefixes->matchingPrefix('Tests\Support\Fixture'));
        self::assertNull($prefixes->matchingPrefix('Tests\Domain'));
    }
}

/**
 * @extends RuleTestCase<ForbiddenNamespaceRule>
 */
#[CoversClass(ForbiddenNamespaceRule::class)]
#[Medium]
final class ForbiddenNamespaceRuleWithoutConfiguredPrefixesTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new ForbiddenNamespaceRule();
    }

    public function testProcessNodeWithoutConfiguredPrefixesDoesNotReport(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbiddenNamespace/WithForbiddenNamespaces.php'], []);
    }
}
