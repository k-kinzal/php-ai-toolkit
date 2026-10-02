<?php

declare(strict_types=1);

namespace Toolkit\Mutation;

use function array_keys;
use function array_map;
use function explode;
use function ltrim;

use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;

use function preg_match;
use function preg_replace;
use function str_contains;
use function trim;

/**
 * Reads the toolkit's compact mutation syntax from a PHPDoc syntax tree.
 */
final class MutationContractReader
{
    /**
     * The exact token accepted at the start of a parameter description.
     */
    public const PARAMETER_MARKER = '+mut';

    /**
     * Reads parameter markers and callable mutation targets.
     */
    public function read(PhpDocNode $node): MutationContract
    {
        $parameters = [];
        $problems = [];
        foreach (['@param', '@psalm-param', '@phpstan-param'] as $tagName) {
            foreach ($node->getTagsByName($tagName) as $tag) {
                if (!$tag->value instanceof ParamTagValueNode) {
                    continue;
                }

                $name = ltrim($tag->value->parameterName, '$');
                if ($this->isMutableDescription($tag->value->description)) {
                    $parameters[$name] = true;
                } elseif (str_contains($tag->value->description, self::PARAMETER_MARKER)) {
                    $problems[] = 'Place +mut immediately after ' . $tag->value->parameterName . ' in the @param tag.';
                }
            }
        }

        $thisTarget = false;
        $globalTarget = false;
        foreach ($node->getTagsByName('@mutation') as $tag) {
            $value = trim((string) $tag->value);
            if ($value === '') {
                $problems[] = 'Give @mutation one or both targets: $this or global.';

                continue;
            }

            foreach (array_map('trim', explode(',', $value)) as $target) {
                if ($target === '$this') {
                    $thisTarget = true;
                } elseif ($target === 'global') {
                    $globalTarget = true;
                } else {
                    $problems[] = 'Replace @mutation target "' . $target . '" with $this or global.';
                }
            }
        }

        return new MutationContract(array_keys($parameters), $thisTarget, $globalTarget, $problems);
    }

    /**
     * Reports whether a parameter description starts with the exact marker.
     */
    public function isMutableDescription(string $description): bool
    {
        return preg_match('/^\+mut(?:\s+|$)/', trim($description)) === 1;
    }

    /**
     * Removes the marker before prose is shown as the parameter description.
     */
    public function cleanDescription(string $description): string
    {
        $description = trim($description);
        if (!$this->isMutableDescription($description)) {
            return $description;
        }

        return trim((string) preg_replace('/^\+mut(?:\s+|$)/', '', $description, 1));
    }
}
