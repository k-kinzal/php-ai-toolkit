<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Assignment;

use Guard\Config\Assignment\FilePolicyAssignment;
use Guard\Config\Profile\PolicyConfig;
use Guard\Config\Value\LimitConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Assignment\FilePolicyAssignment
 * @uses \Guard\Config\Profile\PolicyConfig
 * @uses \Guard\Config\Value\LimitConfig
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
