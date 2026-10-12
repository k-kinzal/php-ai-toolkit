<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Naming;

use Override;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\DependencyInjection\MissingServiceException;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use Toolkit\PhpStan\Rule\Naming\ForbiddenNamespacePrefixes;
use Toolkit\PhpStan\Rule\Naming\ForbiddenNamespaceRule;
use Toolkit\PhpStan\Rule\Naming\NamespacePrefixNormalizer;

/**
 * @extends RuleTestCase<ForbiddenNamespaceRule>
 * @covers \Toolkit\PhpStan\Rule\Naming\ForbiddenNamespaceRule
 * @covers \Toolkit\PhpStan\Rule\Naming\ForbiddenNamespacePrefixes
 * @covers \Toolkit\PhpStan\Rule\Naming\NamespacePrefixNormalizer
 * @medium
 */
#[CoversClass(ForbiddenNamespaceRule::class)]
#[CoversClass(ForbiddenNamespacePrefixes::class)]
#[CoversClass(NamespacePrefixNormalizer::class)]
#[Medium]
final class ForbiddenNamespaceRuleTest extends RuleTestCase
{
    /**
     * @return list<string>
     */
    #[Override]
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../../../../../rules.neon'];
    }

    /**
     * @throws MissingServiceException
     */
    #[Override]
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(ForbiddenNamespaceRule::class);
    }

    /**
     * @throws MissingServiceException
     */
    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PhpParser\Node\Stmt\Namespace_::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeForbiddenNamespacesAreReported(): void
    {
        $this->analyse([__DIR__ . '/../../../../../tests/PhpStan/ForbiddenNamespace/WithForbiddenNamespaces.php'], [
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
            [
                'Move code out of namespace "Tests\Fixture". Use a namespace outside forbidden test prefix "Tests\Fixture", or inline setup in each test.',
                53,
            ],
            [
                'Move code out of namespace "Tests\Fixtures\User". Use a namespace outside forbidden test prefix "Tests\Fixtures", or inline setup in each test.',
                59,
            ],
        ]);
    }

    public function testProcessNodeAllowedNamespacesAreNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../../../tests/PhpStan/ForbiddenNamespace/WithoutForbiddenNamespaces.php'], []);
    }

    public function testProcessNodeDefaultForbiddenSegmentsAreReportedAtEveryDepth(): void
    {
        $this->analyse([__DIR__ . '/../../../../../tests/PhpStan/ForbiddenNamespace/WithForbiddenSegments.php'], [
            ['Namespace "Model" contains forbidden segment "Model". Replace this segment with a domain-specific name and update namespace declarations and references.', 5],
            ['Namespace "Models" contains forbidden segment "Models". Replace this segment with a domain-specific name and update namespace declarations and references.', 8],
            ['Namespace "Infrastructure" contains forbidden segment "Infrastructure". Replace this segment with a domain-specific name and update namespace declarations and references.', 11],
            ['Namespace "Infrastructures" contains forbidden segment "Infrastructures". Replace this segment with a domain-specific name and update namespace declarations and references.', 14],
            ['Namespace "App\Model" contains forbidden segment "Model". Replace this segment with a domain-specific name and update namespace declarations and references.', 17],
            ['Namespace "App\Models" contains forbidden segment "Models". Replace this segment with a domain-specific name and update namespace declarations and references.', 20],
            ['Namespace "App\Infrastructure" contains forbidden segment "Infrastructure". Replace this segment with a domain-specific name and update namespace declarations and references.', 23],
            ['Namespace "App\Infrastructures" contains forbidden segment "Infrastructures". Replace this segment with a domain-specific name and update namespace declarations and references.', 26],
            ['Namespace "Vendor\Billing\Model\User" contains forbidden segment "Model". Replace this segment with a domain-specific name and update namespace declarations and references.', 29],
            ['Namespace "Vendor\Billing\Models\User" contains forbidden segment "Models". Replace this segment with a domain-specific name and update namespace declarations and references.', 32],
            ['Namespace "Vendor\Billing\Infrastructure\Database" contains forbidden segment "Infrastructure". Replace this segment with a domain-specific name and update namespace declarations and references.', 35],
            ['Namespace "Vendor\Billing\Infrastructures\Database" contains forbidden segment "Infrastructures". Replace this segment with a domain-specific name and update namespace declarations and references.', 38],
        ]);
    }

    /**
     * @throws MissingServiceException
     */
    public function testProcessNodeCustomSegmentsDoNotApplyTheDistributedDefaults(): void
    {
        $rule = new ForbiddenNamespaceRule([], ['Legacy']);
        $scope = self::getContainer()->getByType(ScopeFactory::class)->create(ScopeContext::create(__FILE__));
        $errors = $rule->processNode(new \PhpParser\Node\Stmt\Namespace_(new \PhpParser\Node\Name('App\Legacy')), $scope);

        self::assertCount(1, $errors);
        self::assertSame('customRules.forbiddenNamespace', $errors[0]->getIdentifier());
        self::assertSame(
            'Namespace "App\Legacy" contains forbidden segment "Legacy". Replace this segment with a domain-specific name and update namespace declarations and references.',
            $errors[0]->getMessage(),
        );
        self::assertSame([], $rule->processNode(new \PhpParser\Node\Stmt\Namespace_(new \PhpParser\Node\Name('App\Model')), $scope));
    }

    /**
     * @throws MissingServiceException
     */
    public function testProcessNodeEmptySegmentListAllowsModelNamespace(): void
    {
        $rule = new ForbiddenNamespaceRule([], []);

        self::assertSame([], $rule->processNode(
            new \PhpParser\Node\Stmt\Namespace_(new \PhpParser\Node\Name('App\Model')),
            self::getContainer()->getByType(ScopeFactory::class)->create(ScopeContext::create(__FILE__)),
        ));
    }

    /**
     * @throws MissingServiceException
     */
    public function testProcessNodeGlobalNamespaceIsAllowed(): void
    {
        self::assertSame([], $this->getRule()->processNode(
            new \PhpParser\Node\Stmt\Namespace_(),
            self::getContainer()->getByType(ScopeFactory::class)->create(ScopeContext::create(__FILE__)),
        ));
    }

    /**
     * @throws MissingServiceException
     */
    public function testProcessNodeOverlappingRestrictionsReportThePrefixOnce(): void
    {
        $errors = $this->getRule()->processNode(
            new \PhpParser\Node\Stmt\Namespace_(new \PhpParser\Node\Name('Tests\Support\Model')),
            self::getContainer()->getByType(ScopeFactory::class)->create(ScopeContext::create(__FILE__)),
        );

        self::assertCount(1, $errors);
        self::assertSame(
            'Move code out of namespace "Tests\Support\Model". Use a namespace outside forbidden test prefix "Tests\Support", or inline setup in each test.',
            $errors[0]->getMessage(),
        );
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
        $this->analyse([__DIR__ . '/../../../../../tests/PhpStan/ForbiddenNamespace/WithForbiddenNamespaces.php'], []);
    }
}
