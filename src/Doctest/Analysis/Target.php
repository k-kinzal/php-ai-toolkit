<?php

declare(strict_types=1);

namespace PhpAiToolkit\Doctest\Analysis;

use function basename;
use function implode;
use function sprintf;

/**
 * One documented declaration whose PHPDoc block may carry examples.
 *
 * A target is what an example is about: the file, class-like, method, or
 * function the docblock is attached to. The kind decides how the symbol is
 * spelled, which is what makes an example addressable from the command line.
 *
 * @property-read string $kind
 * @property-read string $path
 * @property-read string $docComment
 * @property-read string $name
 * @property-read int $line
 * @property-read string $namespace
 * @property-read ?string $className
 * @property-read list<string> $imports
 * @property-read ?string $displayPath
 *
 * @visibility public
 *
 * @example Naming a documented method
 *     $target = new Target(Target::METHOD, 'src/Ledger.php', '', 'append', 12, 'App', 'Ledger');
 *     $target->symbol() // => 'App\\Ledger::append()'
 *     $target->shortName() // => 'Ledger::append()'
 */
final class Target
{
    /**
     * A docblock at the top of a file, documenting the file itself.
     */
    public const FILE = 'file';

    /**
     * A docblock on a class, interface, trait, or enum declaration.
     */
    public const CLASS_LIKE = 'class';

    /**
     * A docblock on a method of a class-like.
     */
    public const METHOD = 'method';

    /**
     * A docblock on a namespaced or global function.
     */
    public const FUNCTION = 'function';

    /**
     * @param string $kind one of the kind constants of this class
     * @param string $path path to the file the docblock was read from
     * @param string $docComment the raw docblock text, delimiters included
     * @param string $name the declared name, or the file basename for a file target
     * @param int $line the line the docblock starts on
     * @param string $namespace the declaring namespace, empty for the global one
     * @param string|null $className the declaring class-like, for a method target
     * @param list<string> $imports the import statements of the documenting file
     * @param string|null $displayPath the path reports show, when it differs from the readable one
     */
    public function __construct(
        /** @readonly */
        private string $kind,
        /** @readonly */
        private string $path,
        /** @readonly */
        private string $docComment,
        /** @readonly */
        private string $name,
        /** @readonly */
        private int $line,
        /** @readonly */
        private string $namespace = '',
        /** @readonly */
        private ?string $className = null,
        /** @readonly */
        private array $imports = [],
        /** @readonly */
        private ?string $displayPath = null,
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
            'kind' => $this->kind,
            'path' => $this->path,
            'docComment' => $this->docComment,
            'name' => $this->name,
            'line' => $this->line,
            'namespace' => $this->namespace,
            'className' => $this->className,
            'imports' => $this->imports,
            'displayPath' => $this->displayPath,
            default => null,
        };
    }

    /**
     * Returns the fully qualified name the target is addressed by.
     *
     * The spelling is the one a reader would write in code, so an example
     * identifier built from it can be pasted back into a filter argument.
     */
    public function symbol(): string
    {
        $prefix = $this->namespace === '' ? '' : $this->namespace . '\\';

        return match ($this->kind) {
            self::FILE => $this->path,
            self::CLASS_LIKE => $prefix . $this->name,
            self::FUNCTION => $prefix . $this->name . '()',
            default => $prefix . ($this->className ?? '') . '::' . $this->name . '()',
        };
    }

    /**
     * Returns the path a report names the target by.
     *
     * Reports are read next to the sources they are about, so they name a file
     * the way the project names it rather than by its location on one machine.
     */
    public function reportPath(): string
    {
        return $this->displayPath ?? $this->path;
    }

    /**
     * Returns the source text an example of this target is evaluated after.
     *
     * Replaying the namespace and imports of the documenting file is what lets
     * an example name a class the way the file around it names one.
     */
    public function preamble(): string
    {
        $lines = $this->namespace === '' ? [] : [sprintf('namespace %s;', $this->namespace)];
        foreach ($this->imports as $import) {
            $lines[] = $import;
        }

        return $lines === [] ? '' : implode("\n", $lines) . "\n";
    }

    /**
     * Returns the unqualified name used in report headings.
     */
    public function shortName(): string
    {
        return match ($this->kind) {
            self::FILE => basename($this->path),
            self::CLASS_LIKE => $this->name,
            self::FUNCTION => $this->name . '()',
            default => sprintf('%s::%s()', $this->className ?? '', $this->name),
        };
    }
}
