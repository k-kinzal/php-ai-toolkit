<?php

declare(strict_types=1);

namespace Toolkit\PhpStan\Extension\Visibility;

use PhpParser\Node;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\IgnoreErrorExtension;
use PHPStan\Analyser\Scope;

/**
 * Suppresses declaration-unused findings for explicitly public API elements.
 */
final class VisibilityPublicUnusedErrorExtension implements IgnoreErrorExtension
{
    /** @readonly */
    private UnusedErrorIdentifier $unusedErrorIdentifier;

    /** @readonly */
    private PublicApiDeclarationLineResolver $lineResolver;

    /**
     * Creates the extension from identifier and source-declaration detection.
     */
    public function __construct(
        ?UnusedErrorIdentifier $unusedErrorIdentifier = null,
        ?PublicApiDeclarationLineResolver $lineResolver = null,
    ) {
        $this->unusedErrorIdentifier = $unusedErrorIdentifier ?? new UnusedErrorIdentifier();
        $this->lineResolver = $lineResolver ?? new PublicApiDeclarationLineResolver();
    }

    /**
     * Reports whether an unused error belongs to an explicitly public declaration.
     */
    public function shouldIgnore(Error $error, Node $node, Scope $scope): bool
    {
        unset($node, $scope);
        $line = $error->getLine();
        if ($line === null || !$this->unusedErrorIdentifier->matches($error->getIdentifier())) {
            return false;
        }

        return $this->lineResolver->declaresPublicAt($error->getFilePath(), $line);
    }
}
