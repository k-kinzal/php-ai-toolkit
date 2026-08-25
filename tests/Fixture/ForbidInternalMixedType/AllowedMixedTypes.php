<?php

declare(strict_types=1);

namespace Tests\Fixture\ForbidInternalMixedType;

class PublicTypes
{
    public mixed $value;

    protected mixed $extensionValue;

    /**
     * @param array<string, mixed> $input
     * @return array{payload: mixed}
     */
    public function boundary(array $input): array
    {
        return ['payload' => $input['payload'] ?? null];
    }

    protected function extensionPoint(mixed $value): mixed
    {
        return $value;
    }
}

/**
 * @visibility public
 */
final class ExplicitPublicTypes
{
    private string $internalValue = '';

    public function accept(mixed $value): mixed
    {
        $this->internalValue = is_string($value) ? $value : '';

        return $value;
    }
}

function publicFunction(mixed $value): mixed
{
    return $value;
}

/**
 * @visibility namespace
 */
final class MagicProtocol
{
    public function __get(string $name): mixed
    {
        return $name;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function __unserialize(array $data): void
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return [];
    }
}

/**
 * @template T
 * @visibility namespace
 */
final class TemplateBox
{
    /** @var T */
    private $value;

    /**
     * @param T $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @return T
     */
    public function value()
    {
        return $this->value;
    }
}
