<?php

declare(strict_types=1);

namespace Tests\Fixture\ForbidInternalMixedType;

/**
 * @property array{payload: mixed} $virtualPayload
 * @method mixed virtualCall(array<mixed> $input)
 * @phpstan-type Payload array{payload: mixed}
 * @visibility namespace
 */
final class RestrictedTypes
{
    /** @var array<string, mixed> */
    public array $values = [];

    /**
     * @param array{payload: mixed} $payload
     * @return list<mixed>
     */
    public function transform(array $payload): array
    {
        /** @var mixed $local */
        $local = 'value';

        return [$payload['payload'], $local];
    }
}

final class PublicContainer
{
    private mixed $state;

    public mixed $surface;

    protected mixed $extensionSurface;

    public function __construct(private mixed $promotedState)
    {
        $this->state = $promotedState;
    }
}

/**
 * @visibility namespace
 */
function restrictedFunction(mixed $input): mixed
{
    return $input;
}

$closure = static function (mixed $input): mixed {
    return $input;
};

$arrow = static fn (mixed $input): mixed => $input;

/** @var mixed $item */
foreach ([] as $item) {
}

/**
 * @visibility namespace
 */
final class RestrictedLifecycle
{
    public function __construct(mixed $value)
    {
    }
}

final class MemberRestrictedTypes
{
    /**
     * @var mixed
     * @visibility namespace
     */
    public mixed $value;

    /**
     * @visibility namespace
     */
    public function restrictedMember(mixed $value): mixed
    {
        return $value;
    }
}
