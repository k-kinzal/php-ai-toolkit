<?php

declare(strict_types=1);

namespace Tests\Fixture\ForbidInternalMixedType;

interface MixedContract
{
    public function fromInterface(mixed $value): mixed;

    /**
     * @param array<string, mixed> $value
     * @return array<string, mixed>
     */
    public function arrayContract(array $value): array;
}

abstract class MixedParent
{
    abstract public function fromParent(mixed $value): mixed;
}

trait MixedTrait
{
    abstract public function fromTrait(mixed $value): mixed;
}

interface StringContract
{
    public function widenedParameter(string $value): string;
}

/**
 * @visibility namespace
 */
final class MixedImplementation extends MixedParent implements MixedContract, StringContract
{
    use MixedTrait;

    public function fromInterface(mixed $value): mixed
    {
        return $value;
    }

    /**
     * @param array<string, mixed> $value
     * @return array<string, mixed>
     */
    public function arrayContract(array $value): array
    {
        return $value;
    }

    public function fromParent(mixed $value): mixed
    {
        return $value;
    }

    public function fromTrait(mixed $value): mixed
    {
        return $value;
    }

    public function widenedParameter(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }

    public function ownMethod(mixed $value): mixed
    {
        return $value;
    }
}

class UntypedParent
{
    public function value($value)
    {
        return $value;
    }

    public function values(array $values): void
    {
    }
}

/**
 * @visibility namespace
 */
final class UntypedImplementation extends UntypedParent
{
    /**
     * @param mixed $value
     */
    public function value($value): mixed
    {
        return $value;
    }

    /**
     * @param array<mixed> $values
     */
    public function values(array $values): void
    {
    }
}

new class () extends UntypedParent {
    /**
     * @param mixed $value
     */
    public function value($value): string
    {
        return is_string($value) ? $value : '';
    }

    /**
     * @param array<mixed> $values
     */
    public function values(array $values): void
    {
    }
};
