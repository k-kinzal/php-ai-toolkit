<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Config;

use function preg_match;
use function rtrim;
use function sprintf;

use Toolkit\DocGen\DocGenException;

use function trim;

/**
 * Normalizes the address a generated site is published at.
 *
 * The site itself links relatively and needs no address, so this is only
 * read by what has to name a page from outside the site: the social
 * preview tags a link shared elsewhere is rendered from, and the canonical
 * link that says which of several deployments is the documented one.
 */
final class BaseUrl
{
    /**
     * Returns one configured address without its trailing slash.
     *
     * An empty value is the same answer as no value at all: nothing about
     * the site changes, rather than an address that resolves nowhere.
     *
     * @throws DocGenException when the value is not an absolute http address
     */
    public function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        if (preg_match('#^https?://[^\s/?\#]+(/[^\s?\#]*)?$#', $trimmed) !== 1) {
            throw new DocGenException(sprintf(
                'Invalid --base-url value: %s. Use the absolute address the site is published at, such as https://example.github.io/project.',
                $trimmed,
            ));
        }

        return rtrim($trimmed, '/');
    }
}
