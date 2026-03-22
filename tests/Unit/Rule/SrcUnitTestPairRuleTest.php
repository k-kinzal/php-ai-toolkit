<?php

declare(strict_types=1);

namespace Tests\Unit\Rule;

use PhpStanAiRules\Rule\SrcUnitTestPairRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @extends RuleTestCase<SrcUnitTestPairRule>
 */
#[CoversClass(SrcUnitTestPairRule::class)]
final class SrcUnitTestPairRuleTest extends RuleTestCase
{
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
        $this->analyse([__DIR__ . '/../../Fixture/SrcUnitTestPairRule/project/src/MissingTest.php'], [
            [
                'Source file "src/MissingTest.php" requires a matching unit test file "tests/Unit/MissingTestTest.php" to keep behavior verifiable.',
                1,
            ],
        ]);
    }

    public function testProcessNodeSourceFileWithTestIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../Fixture/SrcUnitTestPairRule/project/src/HasTest.php'], []);
    }

    public function testProcessNodeTestFileWithoutSourceIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../Fixture/SrcUnitTestPairRule/project/tests/Unit/OrphanedTest.php'], [
            [
                'Unit test file "tests/Unit/OrphanedTest.php" requires a matching source file "src/Orphaned.php" to avoid stale or orphaned tests.',
                1,
            ],
        ]);
    }

    public function testProcessNodeTestFileWithSourceIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../Fixture/SrcUnitTestPairRule/project/tests/Unit/HasTestTest.php'], []);
    }
}
