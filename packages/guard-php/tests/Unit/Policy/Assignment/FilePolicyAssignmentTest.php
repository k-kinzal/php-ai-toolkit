<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Assignment;

use Guard\Policy\Assignment\FilePolicyAssignment;
use Guard\Policy\Definition\LimitConfig;
use Guard\Policy\Definition\PolicyConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Assignment\FilePolicyAssignment
 * @uses \Guard\Policy\Definition\PolicyConfig
 * @uses \Guard\Policy\Definition\LimitConfig
 */
#[CoversClass(FilePolicyAssignment::class)]
#[UsesClass(PolicyConfig::class)]
#[UsesClass(LimitConfig::class)]
final class FilePolicyAssignmentTest extends TestCase
{
    public function testStoresSelectedPolicyAndRule(): void
    {
        $policy = new PolicyConfig('native-api', 'standard', LimitConfig::fromValues(['file.lines' => 900]));
        $assignment = new FilePolicyAssignment('/project/src/Native.php', 'src/Native.php', $policy, 'native');

        self::assertSame('/project/src/Native.php', $assignment->path);
        self::assertSame('src/Native.php', $assignment->relativePath);
        self::assertSame($policy, $assignment->policy);
        self::assertSame('native', $assignment->rule);
    }
}
