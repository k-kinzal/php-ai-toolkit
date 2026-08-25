<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Package;

use function file_get_contents;
use function is_array;
use function is_file;
use function is_string;
use function json_decode;

/**
 * Reads the installed package names of one composer.lock file.
 *
 * A lock file is the authoritative source for the runtime and dev split:
 * "packages" holds the runtime dependencies and "packages-dev" the ones
 * installed only for development.
 */
final class ComposerLockReader
{
    /**
     * Reads the runtime and dev package names of one composer.lock file.
     *
     * A missing, unreadable, or malformed lock file yields null instead of an
     * error, so documentation generation keeps working without it.
     *
     * @return array{runtime: list<string>, dev: list<string>}|null null when the file cannot be used
     */
    public function read(string $path): ?array
    {
        if (!is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            return null;
        }

        $data = json_decode($contents, true);
        if (!is_array($data)) {
            return null;
        }

        return [
            'runtime' => $this->names($data['packages'] ?? null),
            'dev' => $this->names($data['packages-dev'] ?? null),
        ];
    }

    /**
     * Collects the package names of one composer.lock package section.
     *
     * @return list<string>
     */
    public function names(mixed $section): array
    {
        if (!is_array($section)) {
            return [];
        }

        $names = [];
        foreach ($section as $entry) {
            $name = is_array($entry) ? ($entry['name'] ?? null) : null;
            if (is_string($name) && $name !== '') {
                $names[] = $name;
            }
        }

        return $names;
    }
}
