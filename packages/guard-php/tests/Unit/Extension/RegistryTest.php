<?php

declare(strict_types=1);

namespace Tests\Unit\Extension;

use Guard\Collect\Subject;
use Guard\Execution\Context;
use Guard\Execution\Plan;
use Guard\Extension\Registry;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @covers \Guard\Extension\Registry
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(Registry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Extension\PolicyBinding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Rule::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Reporting\Finding::class)]
final class RegistryTest extends TestCase
{
    public function testAddCollectorDuplicateIdsFailWithoutReplacingTheFirstCollector(): void
    {
        $registry = new Registry();
        $collector = new \Tests\Support\CallbackCollector(static fn (Context $context): array => []);
        $registry->addCollector('custom', $collector);
        self::assertSame([$collector], $registry->collectors());
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('custom');
        $registry->addCollector('custom', $collector);
    }
    public function testAddPolicyDuplicateIdsFailWithoutReplacingTheFirstBinding(): void
    {
        $registry = new Registry();
        $policy = new \Tests\Support\CallbackPolicy(static fn (Subject $subject, Context $context): Plan => new Plan([], []));
        $registry->addPolicy('custom', Subject::class, $policy);
        self::assertSame($policy, $registry->policies()[0]->policy);
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('custom');
        $registry->addPolicy('custom', Subject::class, $policy);
    }
    public function testAddCollectorRejectsEmptyIds(): void
    {
        $this->expectException(PolicyException::class);
        (new Registry())->addCollector('', new \Tests\Support\CallbackCollector(static fn (Context $context): array => []));
    }
    public function testAddPolicyRejectsEmptyIds(): void
    {
        $this->expectException(PolicyException::class);
        (new Registry())->addPolicy('', Subject::class, new \Tests\Support\CallbackPolicy(static fn (Subject $subject, Context $context): Plan => new Plan([], [])));
    }


    public function testCollectorsReturnsRegistrationsInInsertionOrder(): void
    {
        $first = new \Tests\Support\CallbackCollector(static fn (Context $context): array => []);
        $second = new \Tests\Support\CallbackCollector(static fn (Context $context): array => []);
        $registry = new Registry();
        $registry->addCollector('z', $first);
        $registry->addCollector('a', $second);
        self::assertSame([$first, $second], $registry->collectors());
    }
    public function testPoliciesRetainsMultipleBindingsForTheSameSubject(): void
    {
        $policy = new \Tests\Support\CallbackPolicy(static fn (Subject $subject, Context $context): Plan => new Plan([], []));
        $registry = new Registry();
        $registry->addPolicy('z', Subject::class, $policy);
        $registry->addPolicy('a', Subject::class, $policy);
        self::assertSame(['z', 'a'], array_map(static fn (\Guard\Extension\PolicyBinding $binding): string => $binding->id, $registry->policies()));
    }
    public function testAddPolicyRejectsTypesThatAreNotSubjects(): void
    {
        $this->expectException(PolicyException::class);
        (new Registry())->addPolicy('invalid', stdClass::class, new \Tests\Support\CallbackPolicy(static fn (Subject $subject, Context $context): Plan => new Plan([], [])));
    }

}
