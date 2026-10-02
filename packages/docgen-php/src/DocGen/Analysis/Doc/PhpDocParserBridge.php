<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Analysis\Doc;

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
 * Parses PHPDoc text for both phpstan/phpdoc-parser 1 and 2.
 *
 * Version 2 requires a ParserConfig argument on every collaborator while
 * version 1 forbids it, so the constructors are inspected with reflection
 * and the config argument is prepended only when the installed version
 * declares it. This keeps the bridge valid for either major version
 * without referencing classes that only one of them ships.
 */
final class PhpDocParserBridge
{
    private ?PhpDocParser $parser = null;

    private ?Lexer $lexer = null;

    /**
     * Parses one PHPDoc comment into its AST node.
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
     * Builds the leading constructor arguments of the installed version.
     *
     * When the lexer constructor requires a parser configuration object,
     * one is instantiated from the declared parameter type; otherwise the
     * installed version predates the configuration object.
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
