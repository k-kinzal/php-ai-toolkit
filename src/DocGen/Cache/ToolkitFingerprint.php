<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Cache;

use function class_exists;

use Composer\InstalledVersions;

use function hash;
use function implode;

/**
 * Identifies the generator a cache entry was written by.
 *
 * A cache may only be read back by the generator that wrote it: a changed
 * parser or a changed page renderer turns the same sources into different
 * output. The fingerprint is therefore the installed version of this
 * toolkit and of the libraries it parses and prints with, so upgrading any
 * of them leaves nothing of the previous cache to be read.
 *
 * A generator that changed without being installed again — a checkout of
 * the toolkit being worked on, or a patched vendor directory — keeps its
 * fingerprint, because nothing about the installation says otherwise.
 * Generating with --no-cache or --clear-cache is what answers that case,
 * and it is what keeps working on the generator from being paid for by
 * every project that installed it.
 */
final class ToolkitFingerprint
{
    /**
     * The installed packages the generated documentation depends on.
     *
     * @var list<string>
     */
    public const PACKAGES = ['k-kinzal/php-ai-toolkit', 'nikic/php-parser', 'phpstan/phpdoc-parser'];

    private ?string $value = null;

    /**
     * Returns the fingerprint of this generator, computing it once.
     */
    public function value(): string
    {
        $parts = [];
        foreach (self::PACKAGES as $package) {
            $parts[] = $package . "\0" . $this->versionOf($package);
        }

        return $this->value ??= hash('sha256', implode("\n", $parts));
    }

    /**
     * Returns the installed version of one package, with its reference.
     *
     * The reference is what tells two installations of the same branch
     * apart, so a project that follows a development branch of the toolkit
     * still stops reading the cache of the commit before.
     *
     * A package composer knows nothing about has no version to report: an
     * installation without composer runtime metadata is consistent with
     * itself, and a run that needs more than that has --no-cache.
     */
    public function versionOf(string $package): string
    {
        if (!class_exists(InstalledVersions::class) || !InstalledVersions::isInstalled($package)) {
            return '';
        }

        return (string) InstalledVersions::getVersion($package) . '@' . (string) InstalledVersions::getReference($package);
    }
}
