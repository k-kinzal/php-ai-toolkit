<?php

declare(strict_types=1);

namespace DecisionsLint\Source;

/**
 * One declaration a rule can judge, with the text the model reads about it.
 *
 * @property-read string $kind file, class, interface, trait, enum, method, function or property
 * @property-read string $name the short name; the base name for a file
 * @property-read string $qualifiedName the fully qualified name, such as App\Analyzer::analyze
 * @property-read string $namespace the namespace without a leading backslash, or an empty string
 * @property-read string $class the short name of the class-like the target is or belongs to, or an empty string
 * @property-read string $file the path relative to the configuration directory
 * @property-read int $line the 1-based line of the declaration
 * @property-read int $column the 1-based column of the declaration
 * @property-read string $signature the declaration without its body, on one line
 * @property-read string $visibility public, protected or private
 * @property-read list<string> $modifiers the lower-case modifiers
 * @property-read string $declaration the declaration source including its docblock
 * @property-read string $enclosing the source of the enclosing class-like, or of the file for a function
 * @property-read string $fileSource the whole file
 */
final class Target
{
    /**
     * The target that stands for a whole file.
     */
    public const FILE = 'file';

    /**
     * The kinds a rule may select.
     *
     * @var list<string>
     */
    public const KINDS = [self::FILE, 'class', 'interface', 'trait', 'enum', 'method', 'function', 'property'];

    /**
     * The context modes: the declaration alone, its class, or its file.
     *
     * @var list<string>
     */
    public const CONTEXTS = ['declaration', 'class', 'file'];

    /**
     * Creates the immutable value.
     *
     * @param list<string> $modifiers
     */
    public function __construct(
        /** @readonly */
        private string $kind,
        /** @readonly */
        private string $name,
        /** @readonly */
        private string $qualifiedName,
        /** @readonly */
        private string $namespace,
        /** @readonly */
        private string $class,
        /** @readonly */
        private string $file,
        /** @readonly */
        private int $line,
        /** @readonly */
        private int $column,
        /** @readonly */
        private string $signature,
        /** @readonly */
        private string $visibility,
        /** @readonly */
        private array $modifiers,
        /** @readonly */
        private string $declaration,
        /** @readonly */
        private string $enclosing,
        /** @readonly */
        private string $fileSource,
    ) {
    }

    /**
     * Returns a declared immutable property.
     *
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'kind' => $this->kind,
            'name' => $this->name,
            'qualifiedName' => $this->qualifiedName,
            'namespace' => $this->namespace,
            'class' => $this->class,
            'file' => $this->file,
            'line' => $this->line,
            'column' => $this->column,
            'signature' => $this->signature,
            'visibility' => $this->visibility,
            'modifiers' => $this->modifiers,
            'declaration' => $this->declaration,
            'enclosing' => $this->enclosing,
            'fileSource' => $this->fileSource,
            default => null,
        };
    }

    /**
     * Returns the source a rule's context mode asks for: the declaration, its class, or its file.
     */
    public function context(string $mode): string
    {
        return match ($mode) {
            'declaration' => $this->declaration,
            'class' => $this->enclosing,
            default => $this->fileSource,
        };
    }

    /**
     * Returns the values templates may interpolate: file, line, column, kind, name, class, namespace, qualified_name, signature and visibility.
     *
     * @return array<string, string>
     */
    public function variables(): array
    {
        return [
            'file' => $this->file,
            'line' => (string) $this->line,
            'column' => (string) $this->column,
            'kind' => $this->kind,
            'name' => $this->name,
            'class' => $this->class,
            'namespace' => $this->namespace,
            'qualified_name' => $this->qualifiedName,
            'signature' => $this->signature,
            'visibility' => $this->visibility,
        ];
    }
}
