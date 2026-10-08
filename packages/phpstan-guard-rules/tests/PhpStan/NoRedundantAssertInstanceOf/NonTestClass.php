<?php

declare(strict_types=1);

namespace Example\Fixture\NoRedundantAssertInstanceOf;

use PHPUnit\Framework\TestCase;
use Tests\PhpStan\NoRedundantAssertInstanceOf\Reporter;
use Tests\PhpStan\NoRedundantAssertInstanceOf\ReporterInterface;

final class NonTestClass extends TestCase
{
    public function testNamespaceControlsRuleScope(): void
    {
        $reporter = new Reporter();

        self::assertInstanceOf(ReporterInterface::class, $reporter);
    }
}
