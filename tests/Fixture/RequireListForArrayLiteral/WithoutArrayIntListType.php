<?php

declare(strict_types=1);

namespace Tests\Fixture\RequireListForArrayLiteral;

final class WithoutArrayIntListType
{
    /** @var list<string> */
    public array $names = ['foo', 'bar'];

    /** @var array<int, string> */
    public array $emptyMap = [];

    /** @var array<int, string> */
    public array $sparseMap = [2 => 'foo'];

    /** @var array<string, string> */
    public array $namedMap = ['foo', 'bar'];

    /**
     * @param array<int, string> $names
     * @return list<string>
     */
    public function acceptsIntegerKeys(array $names): array
    {
        return ['foo', 'bar'];
    }

    /**
     * @return array<int, string>
     */
    public function emptyMap(): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    public function sparseMap(): array
    {
        return [2 => 'foo'];
    }

    /**
     * @return array<int, string>
     */
    public function uncertainMap(bool $empty): array
    {
        if ($empty) {
            return ['foo'];
        }

        return $this->sparseMap;
    }

    /**
     * @return array<int, string>
     */
    public function mixedLiteralKinds(bool $list): array
    {
        if ($list) {
            return ['foo'];
        }

        return [2 => 'bar'];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function nestedArrayInt(): array
    {
        return ['names' => ['foo', 'bar']];
    }
}
