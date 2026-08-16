<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render\Signature;

use function array_keys;
use function explode;
use function hash;
use function implode;

use PhpAiToolkit\DocGen\Render\RenderKit;

use function preg_match_all;
use function str_contains;
use function strtolower;

/**
 * Digests what the names on a page currently resolve to.
 *
 * A page shows more than the symbols it documents: every name it prints is
 * looked up, and a name that resolves to a documented symbol is printed as
 * a link into that symbol's page and source. A page therefore changes when
 * a symbol it merely names appears, disappears, or moves, which no digest
 * of the page's own data would notice.
 *
 * The names are read out of that data rather than tracked one lookup at a
 * time, and every form a name could resolve to is digested, so a page
 * depends on at least everything it can read and never on less. Nothing is
 * filtered out of the data first: a name that turns out to be a word of
 * prose costs one lookup that resolves to nothing, while a filter that is
 * wrong once costs a page that never notices it changed.
 */
final class SymbolReferenceScanner
{
    /**
     * Matches every name-shaped run of characters, qualified or not.
     */
    public const NAME_PATTERN = '/[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*(?:\\\\[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*)*/';

    /**
     * Digest of a name that resolves to no documented symbol.
     */
    public const UNRESOLVED = '-';

    private ?RenderKit $run = null;

    /** @var array<string, string> */
    private array $digests = [];

    /**
     * Digests every symbol the names in one blob resolve to.
     *
     * @param string $blob the data the page is rendered from
     * @param string $namespace the namespace names on the page are written in
     * @param array<string, string> $useMap the imports names on the page are written under
     */
    public function digest(RenderKit $services, SourceDigestIndex $sources, string $blob, string $namespace, array $useMap): string
    {
        $parts = [];
        foreach ($this->names($blob, $namespace, $useMap) as $name) {
            $parts[] = $name . "\0" . $this->symbolDigest($services, $sources, $name);
        }

        return hash('sha256', implode("\n", $parts));
    }

    /**
     * Lists every name one blob could have a symbol looked up under.
     *
     * A written name resolves through the imports of its file, through the
     * namespace it is written in, or as it stands, so all three readings
     * are listed: which one a renderer takes is its own business, and the
     * ones it does not take resolve to nothing.
     *
     * The names keep the order the blob wrote them in, which is an order
     * of the data rather than of this scan, so the digest of a page that
     * did not change does not change either.
     *
     * @param array<string, string> $useMap
     *
     * @return list<string>
     */
    public function names(string $blob, string $namespace, array $useMap): array
    {
        $matches = [];
        preg_match_all(self::NAME_PATTERN, $blob, $matches);
        $names = array_fill_keys($matches[0], true);
        foreach (array_keys($names) as $written) {
            if ($namespace !== '') {
                $names[$namespace . '\\' . $written] = true;
            }

            $imported = $this->imported($written, $useMap);
            if ($imported !== null) {
                $names[$imported] = true;
            }
        }

        return array_keys($names);
    }

    /**
     * Resolves one written name through the imports of its file.
     *
     * @param array<string, string> $useMap
     */
    public function imported(string $written, array $useMap): ?string
    {
        $first = $written;
        $rest = '';
        if (str_contains($written, '\\')) {
            [$first, $rest] = explode('\\', $written, 2);
            $rest = '\\' . $rest;
        }

        $imported = $useMap[strtolower($first)] ?? null;

        return $imported === null ? null : $imported . $rest;
    }

    /**
     * Digests the symbol one name resolves to, or its absence.
     *
     * The digest of a symbol is the digest of the file it is declared in,
     * because that file decides everything the symbol documents; the
     * package and the kind of source it was found in decide the rest.
     *
     * What a name resolves to holds for the whole run, and the pages of a
     * project name the same symbols over and over, so every name is
     * resolved once per run rather than once per page.
     */
    public function symbolDigest(RenderKit $services, SourceDigestIndex $sources, string $name): string
    {
        if ($this->run !== $services) {
            $this->run = $services;
            $this->digests = [];
        }

        return $this->digests[$name] ??= $this->resolved($services, $sources, $name);
    }

    /**
     * Resolves one name to the digest of the symbol it names.
     */
    public function resolved(RenderKit $services, SourceDigestIndex $sources, string $name): string
    {
        $root = $services->model->root;
        $classLike = $services->model->symbolTable->classLike($name);
        if ($classLike !== null) {
            return 'class-like|' . $sources->of($root, $classLike->file) . '|' . $classLike->packageName . '|' . ($classLike->isDev ? 'dev' : 'src');
        }

        $function = $services->model->symbolTable->functionNamed($name);
        if ($function !== null) {
            return 'function|' . $sources->of($root, $function->file) . '|' . $function->packageName . '|' . ($function->isDev ? 'dev' : 'src');
        }

        return self::UNRESOLVED;
    }
}
