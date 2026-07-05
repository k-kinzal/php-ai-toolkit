<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use Override;
use PhpAiToolkit\PhpStan\Rule\SrcUnitTestPairRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

/**
 * @extends RuleTestCase<SrcUnitTestPairRule>
 */
#[CoversClass(SrcUnitTestPairRule::class)]
#[Medium]
final class SrcUnitTestPairRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new SrcUnitTestPairRule();
    }

    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PHPStan\Node\FileNode::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeSourceFileWithoutTestIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/SrcUnitTestPairRule/project/src/MissingTest.php'], [
            [
                'Create unit test file "tests/Unit/MissingTestTest.php" for source file "src/MissingTest.php".',
                1,
            ],
        ]);
    }

    public function testProcessNodeSourceFileWithTestIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/SrcUnitTestPairRule/project/src/HasTest.php'], []);
    }

    public function testProcessNodeTestFileWithoutSourceIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/SrcUnitTestPairRule/project/tests/Unit/OrphanedTest.php'], [
            [
                'Create source file "src/Orphaned.php" for unit test file "tests/Unit/OrphanedTest.php", or remove the stale test.',
                1,
            ],
        ]);
    }

    public function testProcessNodeTestFileWithSourceIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/SrcUnitTestPairRule/project/tests/Unit/HasTestTest.php'], []);
    }
}
