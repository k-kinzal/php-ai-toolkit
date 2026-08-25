<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Extension\Visibility;

use function array_merge;
use function file_get_contents;
use function get_object_vars;
use function is_array;
use function is_file;

use PhpParser\ErrorHandler\Collecting;
use Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiVisibilityDetector;

/**
 * Finds source lines declaring API elements with @visibility public.
 */
final class PublicApiDeclarationLineResolver
{
    /** @readonly */
    private ParserFactoryBridge $parserBridge;

    /** @readonly */
    private PublicApiVisibilityDetector $visibilityDetector;

    /** @var array<string, list<int>> */
    private array $linesByFile = [];

    /**
     * Creates the resolver from parser compatibility and tag detection.
     */
    public function __construct(
        ?ParserFactoryBridge $parserBridge = null,
        ?PublicApiVisibilityDetector $visibilityDetector = null,
    ) {
        $this->parserBridge = $parserBridge ?? new ParserFactoryBridge();
        $this->visibilityDetector = $visibilityDetector ?? new PublicApiVisibilityDetector();
    }

    /**
     * Reports whether a line declares an explicitly public API element.
     */
    public function declaresPublicAt(string $file, int $line): bool
    {
        return in_array($line, $this->lines($file), true);
    }

    /**
     * Returns every explicitly public declaration line in a source file.
     *
     * @return list<int>
     */
    public function lines(string $file): array
    {
        if (isset($this->linesByFile[$file])) {
            return $this->linesByFile[$file];
        }

        $source = is_file($file) ? file_get_contents($file) : false;
        $parser = $this->parserBridge->parser();
        if ($source === false || $parser === null) {
            return $this->linesByFile[$file] = [];
        }

        $errorHandler = new Collecting();
        $statements = $parser->parse($source, $errorHandler);
        if ($errorHandler->hasErrors()) {
            return $this->linesByFile[$file] = [];
        }

        if ($statements === null) {
            return $this->linesByFile[$file] = [];
        }

        $lines = [];
        foreach ($this->walk($statements) as $node) {
            $docComment = $node->getDocComment();
            if (!$this->isDeclaration($node) || !$this->visibilityDetector->declaresPublic($docComment === null ? null : $docComment->getText())) {
                continue;
            }

            $lines = array_merge($lines, $this->declarationLines($node));
        }

        return $this->linesByFile[$file] = array_values(array_unique($lines));
    }

    /**
     * Returns a node tree in source order.
     *
     * @param array<array-key, \PhpParser\Node|array<array-key, \PhpParser\Node>> $nodes
     * @return list<\PhpParser\Node>
     */
    public function walk(array $nodes): array
    {
        $collected = [];
        foreach ($nodes as $node) {
            if (is_array($node)) {
                $collected = array_merge($collected, $this->walk($node));
                continue;
            }

            $collected[] = $node;
            $values = get_object_vars($node);
            foreach ($node->getSubNodeNames() as $name) {
                $value = $values[$name] ?? null;
                if ($value instanceof \PhpParser\Node) {
                    $collected = array_merge($collected, $this->walk([$value]));
                    continue;
                }

                if (!is_array($value)) {
                    continue;
                }

                $nestedNodes = [];
                foreach ($value as $nested) {
                    if ($nested instanceof \PhpParser\Node) {
                        $nestedNodes[] = $nested;
                    }
                }

                $collected = array_merge($collected, $this->walk($nestedNodes));
            }
        }

        return $collected;
    }

    /**
     * Reports whether a node can carry the @visibility tag.
     */
    public function isDeclaration(\PhpParser\Node $node): bool
    {
        return $node instanceof \PhpParser\Node\Stmt\ClassLike
            || $node instanceof \PhpParser\Node\Stmt\ClassMethod
            || $node instanceof \PhpParser\Node\Stmt\Property
            || $node instanceof \PhpParser\Node\Stmt\ClassConst
            || $node instanceof \PhpParser\Node\Stmt\EnumCase;
    }

    /**
     * Returns every line an unused-member rule may report for one declaration.
     *
     * @return list<int>
     */
    public function declarationLines(\PhpParser\Node $node): array
    {
        $lines = [$node->getStartLine()];
        if ($node instanceof \PhpParser\Node\Stmt\Property) {
            foreach ($node->props as $property) {
                $lines[] = $property->getStartLine();
            }
        }

        if ($node instanceof \PhpParser\Node\Stmt\ClassConst) {
            foreach ($node->consts as $constant) {
                $lines[] = $constant->getStartLine();
            }
        }

        return $lines;
    }
}
