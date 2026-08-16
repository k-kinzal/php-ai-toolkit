<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render\Diff;

use PhpAiToolkit\DocGen\Analysis\Diff\DiffIndex;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffKey;
use PhpAiToolkit\DocGen\Analysis\Diff\DiffStatus;

use function sprintf;

/**
 * The diff state of the page being rendered, as the renderers ask for it.
 *
 * Every state reaches the page as one data-diff attribute and nothing
 * else: which of the three display modes shows it green, red, or not at
 * all is decided in the browser, so one generated site serves all three.
 * Without a comparison the attribute is empty, so a plain site is rendered
 * exactly as it was before diff mode existed.
 */
final class DiffHtml
{
    /** @readonly */
    private ?DiffIndex $index;

    /** @readonly */
    private DiffKey $keys;

    /** @readonly */
    private DiffStatus $combiner;

    /**
     * Creates the diff view of one generation run.
     */
    public function __construct(?DiffIndex $index = null, ?DiffStatus $combiner = null)
    {
        $this->index = $index;
        $this->keys = $index !== null ? $index->keys() : new DiffKey();
        $this->combiner = $combiner ?? new DiffStatus();
    }

    /**
     * Reports whether the site compares two revisions.
     */
    public function isActive(): bool
    {
        return $this->index !== null;
    }

    /**
     * Returns the recorded state of one key.
     */
    public function statusOf(string $key): string
    {
        return $this->index !== null ? $this->index->status($key) : DiffStatus::SAME;
    }

    /**
     * Renders the attribute of one state.
     */
    public function mark(string $status): string
    {
        return $this->index === null ? '' : sprintf(' data-diff="%s"', $status);
    }

    /**
     * Renders the attribute of one key.
     */
    public function attribute(string $key): string
    {
        return $this->mark($this->statusOf($key));
    }

    /**
     * Combines the states of several elements into one.
     *
     * @param list<string> $statuses
     */
    public function combine(array $statuses): string
    {
        return $this->combiner->combine($statuses);
    }

    /**
     * Returns the attribute of the combined state of several elements.
     *
     * @param list<string> $statuses
     */
    public function combined(array $statuses): string
    {
        return $this->mark($this->combine($statuses));
    }

    /**
     * Returns the attribute of a section that no revision can change.
     *
     * Sections such as the relations of a symbol describe the head
     * revision as a whole rather than a change to it, so they are marked
     * unchanged and step aside when only changes are asked for.
     */
    public function unchanged(): string
    {
        return $this->mark(DiffStatus::SAME);
    }

    /**
     * Returns the attribute of one class-like symbol.
     */
    public function classLike(string $fqcn): string
    {
        return $this->attribute($this->keys->classLike($fqcn));
    }

    /**
     * Returns the state of one class-like symbol.
     */
    public function classLikeStatus(string $fqcn): string
    {
        return $this->statusOf($this->keys->classLike($fqcn));
    }

    /**
     * Returns the attribute of one declaration head.
     */
    public function header(string $fqcn): string
    {
        return $this->attribute($this->keys->header($fqcn));
    }

    /**
     * Returns the state of one declaration head.
     */
    public function headerStatus(string $fqcn): string
    {
        return $this->statusOf($this->keys->header($fqcn));
    }

    /**
     * Returns the diff key of one member.
     */
    public function memberKey(string $fqcn, string $kind, string $name): string
    {
        return $this->keys->member($fqcn, $kind, $name);
    }

    /**
     * Returns the attribute of one member.
     */
    public function member(string $fqcn, string $kind, string $name): string
    {
        return $this->attribute($this->keys->member($fqcn, $kind, $name));
    }

    /**
     * Returns the state of one member.
     */
    public function memberStatus(string $fqcn, string $kind, string $name): string
    {
        return $this->statusOf($this->keys->member($fqcn, $kind, $name));
    }

    /**
     * Returns the diff key of one method.
     */
    public function methodKey(string $fqcn, string $name): string
    {
        return $this->keys->member($fqcn, DiffKey::METHOD, $name);
    }

    /**
     * Returns the attribute of one method.
     */
    public function method(string $fqcn, string $name): string
    {
        return $this->attribute($this->methodKey($fqcn, $name));
    }

    /**
     * Returns the attribute of one property.
     */
    public function property(string $fqcn, string $name): string
    {
        return $this->attribute($this->keys->member($fqcn, DiffKey::PROPERTY, $name));
    }

    /**
     * Returns the attribute of one class constant.
     */
    public function constant(string $fqcn, string $name): string
    {
        return $this->attribute($this->keys->member($fqcn, DiffKey::CONSTANT, $name));
    }

    /**
     * Returns the attribute of one enum case.
     */
    public function enumCase(string $fqcn, string $name): string
    {
        return $this->attribute($this->keys->member($fqcn, DiffKey::ENUM_CASE, $name));
    }

    /**
     * Returns the diff key of one top-level function.
     */
    public function functionKey(string $fqn): string
    {
        return $this->keys->functionSymbol($fqn);
    }

    /**
     * Returns the attribute of one top-level function.
     */
    public function functionSymbol(string $fqn): string
    {
        return $this->attribute($this->keys->functionSymbol($fqn));
    }

    /**
     * Returns the attribute of one parameter of a declaration.
     *
     * @param string $ownerKey the member or function key of the declaration
     */
    public function parameter(string $ownerKey, string $name): string
    {
        return $this->attribute($this->keys->parameter($ownerKey, $name));
    }

    /**
     * Returns the state of one parameter of a declaration.
     *
     * @param string $ownerKey the member or function key of the declaration
     */
    public function parameterStatus(string $ownerKey, string $name): string
    {
        return $this->statusOf($this->keys->parameter($ownerKey, $name));
    }

    /**
     * Returns the attribute of the return type of a declaration.
     *
     * @param string $ownerKey the member or function key of the declaration
     */
    public function returnType(string $ownerKey): string
    {
        return $this->attribute($this->keys->returnType($ownerKey));
    }

    /**
     * Returns the attribute of the throws tags of a declaration.
     *
     * @param string $ownerKey the member or function key of the declaration
     */
    public function throwsTags(string $ownerKey): string
    {
        return $this->attribute($this->keys->throwsTags($ownerKey));
    }

    /**
     * Returns the state of one listed symbol, whatever its kind.
     */
    public function symbolStatus(string $kind, string $fqcn): string
    {
        return $kind === 'function'
            ? $this->statusOf($this->keys->functionSymbol($fqcn))
            : $this->statusOf($this->keys->classLike($fqcn));
    }

    /**
     * Returns the state of one namespace of a package.
     */
    public function namespaceStatus(string $packageName, string $namespace): string
    {
        return $this->statusOf($this->keys->namespaceName($packageName, $namespace));
    }

    /**
     * Returns the state of one documented package.
     */
    public function packageStatus(string $packageName): string
    {
        return $this->statusOf($this->keys->package($packageName));
    }

    /**
     * Returns the state of one rendered Markdown document.
     */
    public function documentStatus(string $packageName, string $path): string
    {
        return $this->statusOf($this->keys->document($packageName, $path));
    }

    /**
     * Reads one project-relative file as it was in the base revision.
     */
    public function baseSource(string $relativeFile): ?string
    {
        return $this->index !== null ? $this->index->baseSource($relativeFile) : null;
    }

    /**
     * Returns the label of the compared base revision.
     */
    public function baseLabel(): string
    {
        return $this->index !== null ? $this->index->baseLabel() : '';
    }

    /**
     * Returns the label of the compared head revision.
     */
    public function headLabel(): string
    {
        return $this->index !== null ? $this->index->headLabel() : '';
    }
}
