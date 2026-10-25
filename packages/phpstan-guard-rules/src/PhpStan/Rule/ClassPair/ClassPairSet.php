<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\ClassPair;

/**
 * Resolves configured class pair definitions against a project's Composer autoload map.
 */
final class ClassPairSet
{
    /**
     * The shipped pair: each class under the namespace of src/ needs a *Test class under the namespace of tests/Unit/.
     */
    public const DEFAULTS = [
        ['from' => '${src}\{name}', 'to' => '${tests/Unit}\{name}Test'],
    ];

    /**
     * @var list<ClassPair>
     * @readonly
     */
    private array $pairs;

    /**
     * @param ComposerAutoloadMap $autoload PSR-4 entries that resolve "${directory}" placeholders
     * @param list<array{from: string, to: string, exclude?: list<string>}> $definitions configured pairs
     * @param list<string> $exclude class name globs that no pair checks
     */
    public function __construct(ComposerAutoloadMap $autoload, array $definitions = self::DEFAULTS, array $exclude = [])
    {
        $expander = new AutoloadPlaceholderExpander($autoload);
        $pairs = [];
        foreach ($definitions as $definition) {
            $from = $expander->expand($definition['from']);
            $to = $expander->expand($definition['to']);
            if ($from === null || $to === null) {
                continue;
            }

            $excluded = [];
            foreach (array_merge($exclude, $definition['exclude'] ?? []) as $pattern) {
                $expanded = $expander->expand($pattern);
                if ($expanded !== null) {
                    $excluded[] = $expanded;
                }
            }

            $pairs[] = new ClassPair(new ClassNamePattern($from), new ClassNamePattern($to), new ClassNameGlob($excluded));
        }

        $this->pairs = $pairs;
    }

    /**
     * Returns the pairs whose placeholders all resolved.
     *
     * @return list<ClassPair>
     */
    public function pairs(): array
    {
        return $this->pairs;
    }
}
