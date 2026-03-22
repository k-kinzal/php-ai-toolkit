<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture\ForbidDescriptivePhpDocInTestClass;

class WithDescriptionAndTag
{
    /**
     * Tests the calculation with various inputs.
     *
     * @dataProvider providerCalculation
     */
    public function testCalculation(int $input, int $expected): void
    {
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function providerCalculation(): array
    {
        return [
            'zero' => [0, 0],
            'positive' => [5, 10],
        ];
    }
}
