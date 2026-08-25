<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Rule\Type;

use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\Rules\IdentifierRuleError;
use Toolkit\PhpStan\Rule\PhpDoc\RulePhpDocParser;

/**
 * Collects mixed from explicit local @var declarations.
 *
 * @visibility namespace
 */
final class MixedLocalPhpDocErrorCollector
{
    /** @readonly */
    private PhpDocMixedTypeInspector $typeInspector;

    /** @readonly */
    private MixedTypeErrorBuilder $errorBuilder;

    /** @readonly */
    private RulePhpDocParser $parser;

    /**
     * Creates the collector from syntax inspection and PHPDoc parsing.
     */
    public function __construct(
        ?PhpDocMixedTypeInspector $typeInspector = null,
        ?MixedTypeErrorBuilder $errorBuilder = null,
        ?RulePhpDocParser $parser = null,
    ) {
        $this->typeInspector = $typeInspector ?? new PhpDocMixedTypeInspector();
        $this->errorBuilder = $errorBuilder ?? new MixedTypeErrorBuilder();
        $this->parser = $parser ?? new RulePhpDocParser();
    }

    /**
     * Collects local errors from a statement's effective @var family.
     *
     * @return list<IdentifierRuleError>
     */
    public function errors(\PhpParser\Node\Stmt $node, string $scopeSymbol): array
    {
        $docComment = $node->getDocComment();
        if ($docComment === null) {
            return [];
        }

        $phpDoc = $this->parser->parse($docComment->getText());
        foreach (['@phpstan-var', '@psalm-var', '@var'] as $tagName) {
            $tags = $phpDoc->getTagsByName($tagName);
            if ($tags === []) {
                continue;
            }

            return $this->tagErrors($tags, $node, $scopeSymbol);
        }

        return [];
    }

    /**
     * Collects errors from the highest-precedence tag family present.
     *
     * @param array<int|string, \PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode> $tags
     * @return list<IdentifierRuleError>
     */
    public function tagErrors(array $tags, \PhpParser\Node\Stmt $node, string $scopeSymbol): array
    {
        $errors = [];
        foreach ($tags as $tag) {
            if (!$tag->value instanceof VarTagValueNode || !$this->typeInspector->contains($tag->value->type)) {
                continue;
            }
            $variableName = $tag->value->variableName;
            $label = $variableName !== '' ? $variableName : 'expression';
            $errors[] = $this->errorBuilder->build((string) $tag->value->type, 'local @var ' . $label, $scopeSymbol, $node->getStartLine());
        }

        return $errors;
    }
}
