<?php

declare(strict_types=1);

namespace Tests\Unit\Extension;

use Guard\Collect\Subject;
use Guard\Execution\Context;
use Guard\Execution\Plan;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Extension\PolicyBinding
 * @uses \Guard\Collect\Tree\DirectoryTree
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\Registry
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(\Guard\Extension\PolicyBinding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Tree\DirectoryTree::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Extension\Registry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Rule::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Reporting\Finding::class)]
final class PolicyBindingTest extends TestCase
{
    public function testBindingKeepsTheExplicitInformationContract(): void
    {
        $policy = new \Tests\Support\CallbackPolicy(static fn (Subject $subject, Context $context): Plan => new Plan([], []));
        $binding = new \Guard\Extension\PolicyBinding('example', \Guard\Collect\Tree\DirectoryTree::class, $policy);
        self::assertSame('example', $binding->id);
        self::assertSame(\Guard\Collect\Tree\DirectoryTree::class, $binding->informationType);
        self::assertSame($policy, $binding->policy);
    }

}
