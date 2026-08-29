<?php

declare(strict_types=1);

namespace Toolkit\LocGuard\Analysis;

use Toolkit\LocGuard\Config\Policy\PolicyConfig;

/**
 * One discovered PHP file with its selected metric policy.
 *
 * @property-read string $path
 * @property-read string $relativePath
 * @property-read PolicyConfig $policy
 * @property-read ?string $rule
 */
final class FilePolicyAssignment
{
    /**
     * Creates a file-to-policy assignment.
     */
    public function __construct(
        /** @readonly */
        private string $path,
        /** @readonly */
        private string $relativePath,
        /** @readonly */
        private PolicyConfig $policy,
        /** @readonly */
        private ?string $rule,
    ) {
    }

    /**
     * Provides read-only access to the immutable properties.
     *
     * @return mixed the value of the requested property
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'path' => $this->path,
            'relativePath' => $this->relativePath,
            'policy' => $this->policy,
            'rule' => $this->rule,
            default => null,
        };
    }
}
