<?php

declare(strict_types=1);

namespace DecisionsLint\Source;

/**
 * Turns the declarations of a file into targets with positions, names and source text.
 */
final class TargetExtractor
{
    /**
     * Creates an extractor, optionally with a shared scanner.
     */
    public function __construct(private ?DeclarationScanner $scanner = null)
    {
    }

    /**
     * Returns the target that stands for the whole file.
     */
    public function file(string $path, string $code): Target
    {
        return new Target(Target::FILE, basename($path), $path, '', '', $path, 1, 1, $path, 'public', [], $code, $code, $code);
    }

    /**
     * Returns every class-like, method, function and property of a PHP file as a target.
     *
     * @return list<Target>
     */
    public function extract(string $path, string $code): array
    {
        $tokens = new Tokens($code);
        $declarations = ($this->scanner ?? new DeclarationScanner())->scan($tokens);
        $owners = [];
        foreach ($declarations as $declaration) {
            if ($declaration->owner === null && $declaration->kind !== 'function') {
                $owners[$declaration->name] = $tokens->text($declaration->docStart, $declaration->end);
            }
        }
        $targets = [];
        foreach ($declarations as $declaration) {
            $targets[] = $this->target($path, $code, $tokens, $declaration, $owners);
        }

        return $targets;
    }

    /**
     * Builds the target of one declaration.
     *
     * @param array<string, string> $owners the source of every top-level class-like by short name
     */
    public function target(string $path, string $code, Tokens $tokens, Declaration $declaration, array $owners): Target
    {
        $namespace = $declaration->namespace;
        $owner = $declaration->owner;
        $class = $owner ?? ($declaration->kind === 'function' ? '' : $declaration->name);
        $qualified = ($namespace === '' ? '' : $namespace . '\\') . ($owner === null ? $declaration->name : $owner . ($declaration->kind === 'property' ? '::$' : '::') . $declaration->name);
        $enclosing = $owner === null ? ($declaration->kind === 'function' ? $code : $tokens->text($declaration->docStart, $declaration->end)) : ($owners[$owner] ?? $code);
        $signature = trim((string) preg_replace('/\s+/', ' ', $tokens->text($declaration->start, $declaration->nameEnd)));

        return new Target($declaration->kind, $declaration->name, $qualified, $namespace, $class, $path, $tokens->at($declaration->start)->line, $tokens->column($declaration->start), $signature, $declaration->visibility(), $declaration->modifiers, $tokens->text($declaration->docStart, $declaration->end), $enclosing, $code);
    }
}
