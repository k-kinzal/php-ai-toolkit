<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Config;

use function is_string;

use PhpAiToolkit\DocGen\DocGenException;

use function preg_match;
use function rtrim;
use function sprintf;
use function trim;

/**
 * Normalizes the address of the repository a documented project lives in.
 *
 * A generated site is the read side of a repository, and a reader who found
 * an answer in it usually wants the code that answer was read from, so the
 * site names where that code lives. Only an absolute http address can be
 * linked to from a page: a value that is not one is rejected where a project
 * configured it, and ignored where it merely stands in a manifest that is
 * read for other reasons.
 */
final class RepositoryUrl
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

    /**
     * Returns one configured address without its trailing slash.
     *
     * An empty value is the same answer as no value at all: the site names
     * no repository, rather than one that resolves nowhere.
     *
     * @throws DocGenException when the value is not an absolute http address
     */
    public function normalize(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $url = $this->read($value);
        if ($url === null) {
            throw new DocGenException(sprintf(
                'Invalid --repository value: %s. Use the absolute address of the repository the project lives in, such as https://github.com/example/project.',
                trim($value),
            ));
        }

        return $url;
    }
}
