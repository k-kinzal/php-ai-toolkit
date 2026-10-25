<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\ClassPair;

/**
 * A fully qualified class name template with one "{name}" placeholder.
 */
final class ClassNamePattern
{
    /** @readonly */
    private string $prefix;

    /** @readonly */
    private string $suffix;

    /**
     * @param string $pattern class name template; a pattern without "{name}" is a namespace prefix
     */
    public function __construct(
        /** @readonly */
        private string $pattern,
    ) {
        $position = strpos($pattern, '{name}');
        if ($position === false) {
            $this->prefix = $pattern === '' ? '' : rtrim($pattern, '\\') . '\\';
            $this->suffix = '';

            return;
        }

        $this->prefix = substr($pattern, 0, $position);
        $this->suffix = substr($pattern, $position + strlen('{name}'));
    }

    /**
     * Returns the template as configured.
     */
    public function pattern(): string
    {
        return $this->pattern;
    }

    /**
     * Returns the text "{name}" stands for in the class, or null when the class does not match.
     */
    public function capture(string $class): ?string
    {
        $fixed = strlen($this->prefix) + strlen($this->suffix);
        if (strlen($class) <= $fixed || !str_starts_with($class, $this->prefix) || !str_ends_with($class, $this->suffix)) {
            return null;
        }

        $name = substr($class, strlen($this->prefix), strlen($class) - $fixed);
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*(?:\\\\[A-Za-z_][A-Za-z0-9_]*)*$/D', $name) !== 1) {
            return null;
        }

        return $name;
    }

    /**
     * Returns the class name produced by substituting "{name}".
     */
    public function expand(string $name): string
    {
        return $this->prefix . $name . $this->suffix;
    }
}
