<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Discovery\Package;

/**
 * Reads a browsable repository address from package metadata.
 */
final class RepositoryAddress
{
    /**
     * Returns one repository address, or null when there is none to link to.
     *
     * Anything that is not an absolute http address is no address at all
     * here, because a page can only link to what a browser can follow.
     */
    public function read(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        if (preg_match('#^https?://[^\s/?\#]+(/[^\s?\#]*)?$#', $trimmed) !== 1) {
            return null;
        }

        return rtrim($trimmed, '/');
    }
}
