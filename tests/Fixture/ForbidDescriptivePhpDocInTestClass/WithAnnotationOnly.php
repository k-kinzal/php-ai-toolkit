<?php

declare(strict_types=1);

namespace Tests\Unit\Fixture\ForbidDescriptivePhpDocInTestClass;

class WithAnnotationOnly
{
    /**
     * @dataProvider providerValues
     */
    public function testWithDataProvider(int $value): void
    {
    }

    /**
     * @return array<string, array{int}>
     */
    public static function providerValues(): array
    {
        return [
            'one' => [1],
            'two' => [2],
        ];
    }
}
