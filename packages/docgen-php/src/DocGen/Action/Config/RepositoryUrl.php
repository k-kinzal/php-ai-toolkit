<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Action\Config;

use function sprintf;

use Toolkit\DocGen\Discovery\Package\RepositoryAddress;
use Toolkit\DocGen\DocGenException;

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

        $url = (new RepositoryAddress())->read($value);
        if ($url === null) {
            throw new DocGenException(sprintf(
                'Invalid --repository value: %s. Use the absolute address of the repository the project lives in, such as https://github.com/example/project.',
                trim($value),
            ));
        }

        return $url;
    }
}
