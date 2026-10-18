<?php

declare(strict_types=1);

namespace DecisionsLint\Source;

/**
 * Where one declaration sits in a token stream.
 *
 * @property-read string $kind class, interface, trait, enum, method, function or property
 * @property-read string $name the short name
 * @property-read string $namespace the namespace the declaration is in, without a leading backslash
 * @property-read ?string $owner the short name of the enclosing class-like, or null
 * @property-read int $docStart the index of the docblock, or of the first token of the declaration
 * @property-read int $start the index of the first modifier or keyword of the declaration
 * @property-read int $nameEnd the index of the last token of the signature before the body
 * @property-read int $end the index of the closing brace or semicolon
 * @property-read list<string> $modifiers the lower-case modifiers, such as public and static
 */
final class Declaration
{
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
        private string $namespace,
        /** @readonly */
        private ?string $owner,
        /** @readonly */
        private int $docStart,
        /** @readonly */
        private int $start,
        /** @readonly */
        private int $nameEnd,
        /** @readonly */
        private int $end,
        /** @readonly */
        private array $modifiers,
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
            'namespace' => $this->namespace,
            'owner' => $this->owner,
            'docStart' => $this->docStart,
            'start' => $this->start,
            'nameEnd' => $this->nameEnd,
            'end' => $this->end,
            'modifiers' => $this->modifiers,
            default => null,
        };
    }

    /**
     * Returns the visibility: public, protected or private, with public as the default.
     */
    public function visibility(): string
    {
        foreach (['private', 'protected', 'public'] as $visibility) {
            if (in_array($visibility, $this->modifiers, true)) {
                return $visibility;
            }
        }

        return 'public';
    }
}
