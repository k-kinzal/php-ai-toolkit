<?php

declare(strict_types=1);

namespace DecisionsLint\Source;

use PhpToken;

/**
 * Finds the class-likes, methods, functions and properties a PHP file declares.
 *
 * Bodies of methods and functions are skipped, so closures and classes inside
 * them are not reported; anonymous classes are skipped as well.
 */
final class DeclarationScanner
{
    /**
     * The modifiers that may precede a declaration keyword.
     *
     * @var list<string>
     */
    private const MODIFIERS = ['public', 'protected', 'private', 'static', 'abstract', 'final', 'readonly', 'var'];

    /**
     * Returns every declaration in source order.
     *
     * @return list<Declaration>
     */
    public function scan(Tokens $tokens): array
    {
        $declarations = [];
        $namespace = '';
        $owners = [];
        $index = 0;
        while ($index < $tokens->count()) {
            $token = $tokens->at($index);
            $owner = $owners === [] ? null : $owners[count($owners) - 1];
            if ($token->is(T_NAMESPACE)) {
                $namespace = $this->namespaceName($tokens, $index);
            } elseif ($this->isClassLikeKeyword($tokens, $index)) {
                $declaration = $this->classLike($tokens, $index, $namespace, $owner['name'] ?? null);
                $declarations[] = $declaration;
                $owners[] = ['name' => $declaration->name, 'end' => $declaration->end];
                $index = $tokens->find($index, ['{']) ?? $declaration->end;
            } elseif ($token->is(T_FUNCTION) && $this->isNamedFunction($tokens, $index)) {
                $declaration = $this->function($tokens, $index, $namespace, $owner['name'] ?? null);
                $declarations[] = $declaration;
                $index = $declaration->end;
            } elseif ($owner !== null && $this->startsProperty($tokens, $index)) {
                $declaration = $this->property($tokens, $index, $namespace, $owner['name']);
                $declarations[] = $declaration;
                $index = $declaration->end;
            } elseif ($owner !== null && $index === $owner['end']) {
                array_pop($owners);
            }
            ++$index;
        }

        return $declarations;
    }

    /**
     * Reads the name after a namespace keyword, or an empty string for the global namespace block.
     */
    public function namespaceName(Tokens $tokens, int $index): string
    {
        $next = $tokens->nextSignificant($index);
        if ($next === null || !$tokens->at($next)->is([T_STRING, T_NAME_QUALIFIED])) {
            return '';
        }

        return $tokens->at($next)->text;
    }

    /**
     * Tells whether the token at an index is the keyword of a named class, interface, trait or enum declaration.
     */
    public function isClassLikeKeyword(Tokens $tokens, int $index): bool
    {
        $token = $tokens->at($index);
        if (!$token->is([T_CLASS, T_INTERFACE, T_TRAIT]) && !$this->isEnumKeyword($token)) {
            return false;
        }
        $previous = $tokens->previousSignificant($index);
        if ($previous !== null && $tokens->at($previous)->is([T_NEW, T_DOUBLE_COLON])) {
            return false;
        }
        $next = $tokens->nextSignificant($index);

        return $next !== null && $tokens->at($next)->is(T_STRING) && $this->followsClassName($tokens, $next);
    }

    /**
     * Tells whether a token is an enum keyword, which PHP 8.0 tokenizes as a plain string.
     */
    public function isEnumKeyword(PhpToken $token): bool
    {
        return strtolower($token->text) === 'enum' && ($token->is(T_STRING) || $token->getTokenName() === 'T_ENUM');
    }

    /**
     * Tells whether the token after a class-like name is one that may follow it: a brace, extends, implements or an enum backing type.
     */
    public function followsClassName(Tokens $tokens, int $nameIndex): bool
    {
        $after = $tokens->nextSignificant($nameIndex);
        if ($after === null) {
            return false;
        }
        $token = $tokens->at($after);

        return $token->text === '{' || $token->text === ':' || $token->is([T_EXTENDS, T_IMPLEMENTS]);
    }

    /**
     * Tells whether a function keyword starts a named function or method rather than a closure.
     */
    public function isNamedFunction(Tokens $tokens, int $index): bool
    {
        $next = $tokens->nextSignificant($index);
        if ($next !== null && $tokens->at($next)->text === '&') {
            $next = $tokens->nextSignificant($next);
        }

        return $next !== null && $tokens->at($next)->is(T_STRING);
    }

    /**
     * Tells whether a token at class member level begins a property declaration: the first modifier of a statement that declares a variable and is not a method, constant or case.
     */
    public function startsProperty(Tokens $tokens, int $index): bool
    {
        if (!in_array(strtolower($tokens->at($index)->text), self::MODIFIERS, true)) {
            return false;
        }
        $previous = $tokens->previousSignificant($index);
        if ($previous !== null && in_array(strtolower($tokens->at($previous)->text), self::MODIFIERS, true)) {
            return false;
        }
        $end = $tokens->find($index, [';', '{', '}']);
        if ($end === null || $tokens->at($end)->text !== ';') {
            return false;
        }
        $variable = false;
        for ($i = $index; $i < $end; ++$i) {
            if ($tokens->at($i)->is([T_FUNCTION, T_CONST, T_CASE, T_USE])) {
                return false;
            }
            $variable = $variable || $tokens->at($i)->is(T_VARIABLE);
        }

        return $variable;
    }

    /**
     * Describes the class-like declaration whose keyword is at an index.
     */
    public function classLike(Tokens $tokens, int $index, string $namespace, ?string $owner): Declaration
    {
        $nameIndex = (int) $tokens->nextSignificant($index);
        $open = (int) $tokens->find($nameIndex, ['{']);
        $start = $this->declarationStart($tokens, $index);

        return new Declaration(strtolower($tokens->at($index)->text), $tokens->at($nameIndex)->text, $namespace, $owner, $this->docStart($tokens, $start), $start, (int) $tokens->previousSignificant($open), $tokens->closing($open), $this->modifiers($tokens, $start, $index));
    }

    /**
     * Describes the function or method whose keyword is at an index.
     */
    public function function(Tokens $tokens, int $index, string $namespace, ?string $owner): Declaration
    {
        $nameIndex = (int) $tokens->nextSignificant($index);
        if ($tokens->at($nameIndex)->text === '&') {
            $nameIndex = (int) $tokens->nextSignificant($nameIndex);
        }
        $parameters = (int) $tokens->find($nameIndex, ['(']);
        $bodyStart = (int) $tokens->find($tokens->closing($parameters, '(', ')'), ['{', ';']);
        $end = $tokens->at($bodyStart)->text === '{' ? $tokens->closing($bodyStart) : $bodyStart;
        $start = $this->declarationStart($tokens, $index);

        return new Declaration($owner === null ? 'function' : 'method', $tokens->at($nameIndex)->text, $namespace, $owner, $this->docStart($tokens, $start), $start, (int) $tokens->previousSignificant($bodyStart), $end, $this->modifiers($tokens, $start, $index));
    }

    /**
     * Describes the property declaration whose first modifier is at an index.
     */
    public function property(Tokens $tokens, int $index, string $namespace, string $owner): Declaration
    {
        $end = (int) $tokens->find($index, [';']);
        $variable = $index;
        for ($i = $index; $i < $end; ++$i) {
            if ($tokens->at($i)->is(T_VARIABLE)) {
                $variable = $i;
                break;
            }
        }

        return new Declaration('property', ltrim($tokens->at($variable)->text, '$'), $namespace, $owner, $this->docStart($tokens, $index), $index, $end, $end, $this->modifiers($tokens, $index, $variable));
    }

    /**
     * Returns the index of the first modifier before a declaration keyword, or the keyword index itself.
     */
    public function declarationStart(Tokens $tokens, int $index): int
    {
        $start = $index;
        $previous = $tokens->previousSignificant($index);
        while ($previous !== null && in_array(strtolower($tokens->at($previous)->text), self::MODIFIERS, true)) {
            $start = $previous;
            $previous = $tokens->previousSignificant($previous);
        }

        return $start;
    }

    /**
     * Returns the index of the docblock directly above a declaration start, or the start itself.
     */
    public function docStart(Tokens $tokens, int $start): int
    {
        for ($i = $start - 1; $i >= 0; --$i) {
            $token = $tokens->at($i);
            if ($token->is(T_DOC_COMMENT)) {
                return $i;
            }
            if (!$token->is(T_WHITESPACE)) {
                return $start;
            }
        }

        return $start;
    }

    /**
     * Returns the lower-case modifier keywords between two indexes.
     *
     * @return list<string>
     */
    public function modifiers(Tokens $tokens, int $from, int $to): array
    {
        $modifiers = [];
        for ($i = $from; $i < $to; ++$i) {
            $text = strtolower($tokens->at($i)->text);
            if (in_array($text, self::MODIFIERS, true)) {
                $modifiers[] = $text;
            }
        }

        return $modifiers;
    }
}
