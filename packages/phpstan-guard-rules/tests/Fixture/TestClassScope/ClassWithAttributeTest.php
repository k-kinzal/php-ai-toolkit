<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture\TestClassScope;

use PHPUnit\Framework\Attributes\Test;

final class ClassWithAttributeTest
{
    #[Test]
    public function checksExample(): void
    {
    }

    public static function providerExamples(): array
    {
        return [];
    }
}
