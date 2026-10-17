<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Extension\Visibility;

use function array_merge;
use function file_get_contents;
use function get_object_vars;
use function is_array;
use function is_file;

use PhpParser\ErrorHandler\Collecting;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\EnumCase;
use PhpParser\Node\Stmt\Property;
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
     * @param array<array-key, Node|array<array-key, Node>> $nodes
     * @return list<Node>
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
                if ($value instanceof Node) {
                    $collected = array_merge($collected, $this->walk([$value]));
                    continue;
                }

                if (!is_array($value)) {
                    continue;
                }

                $nestedNodes = [];
                foreach ($value as $nested) {
                    if ($nested instanceof Node) {
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
    public function isDeclaration(Node $node): bool
    {
        return $node instanceof ClassLike
            || $node instanceof ClassMethod
            || $node instanceof Property
            || $node instanceof ClassConst
            || $node instanceof EnumCase;
    }

    /**
     * Returns every line an unused-member rule may report for one declaration.
     *
     * @return list<int>
     */
    public function declarationLines(Node $node): array
    {
        $lines = [$node->getStartLine()];
        if ($node instanceof Property) {
            foreach ($node->props as $property) {
                $lines[] = $property->getStartLine();
            }
        }

        if ($node instanceof ClassConst) {
            foreach ($node->consts as $constant) {
                $lines[] = $constant->getStartLine();
            }
        }

        return $lines;
    }
}
