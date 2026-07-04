<?php

declare(strict_types=1);

namespace Example\Fixture\NoRedundantAssertInstanceOf;

use PHPUnit\Framework\TestCase;
use Tests\Fixture\NoRedundantAssertInstanceOf\Reporter;
use Tests\Fixture\NoRedundantAssertInstanceOf\ReporterInterface;

final class NonTestClass extends TestCase
{
    public function testNamespaceControlsRuleScope(): void
    {
        $reporter = new Reporter();

        self::assertInstanceOf(ReporterInterface::class, $reporter);
    }
}
