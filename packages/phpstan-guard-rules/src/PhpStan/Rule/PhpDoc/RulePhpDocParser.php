<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\PhpDoc;

use function array_merge;
use function class_exists;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use ReflectionClass;
use ReflectionNamedType;

/**
 * Parses rule-owned PHPDoc text with phpdoc-parser 1 or 2.
 *
 * Version 2 adds a parser configuration argument that version 1 does not
 * accept. Reflecting the lexer constructor lets the rule use the declared
 * API of either installed major without naming a version-specific class.
 */
final class RulePhpDocParser
{
    private ?PhpDocParser $parser = null;

    private ?Lexer $lexer = null;

    /**
     * Parses one PHPDoc comment into its syntax tree.
     */
    public function parse(string $docComment): PhpDocNode
    {
        if ($this->parser === null || $this->lexer === null) {
            $arguments = $this->configArguments();
            $this->lexer = (new ReflectionClass(Lexer::class))->newInstanceArgs($arguments);
            $constExprParser = (new ReflectionClass(ConstExprParser::class))->newInstanceArgs($arguments);
            $typeParser = (new ReflectionClass(TypeParser::class))->newInstanceArgs(array_merge($arguments, [$constExprParser]));
            $this->parser = (new ReflectionClass(PhpDocParser::class))->newInstanceArgs(array_merge($arguments, [$typeParser, $constExprParser]));
        }

        $tokens = new TokenIterator($this->lexer->tokenize($docComment));

        return $this->parser->parse($tokens);
    }

    /**
     * Returns the leading constructor arguments required by the installed major.
     *
     * @return list<object>
     */
    public function configArguments(): array
    {
        $constructor = (new ReflectionClass(Lexer::class))->getConstructor();
        if ($constructor === null || $constructor->getNumberOfRequiredParameters() === 0) {
            return [];
        }

        $parameterType = $constructor->getParameters()[0]->getType();
        if (!$parameterType instanceof ReflectionNamedType) {
            return [];
        }

        $configClass = $parameterType->getName();
        if (!class_exists($configClass)) {
            return [];
        }

        return [(new ReflectionClass($configClass))->newInstance([])];
    }
}
