<?php

declare(strict_types=1);

namespace Toolkit\Compatibility;

use PhpParser\Node;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\Scope;

/**
 * Compatibility contract for PHPStan versions before IgnoreErrorExtension existed.
 */
interface IgnoreErrorExtension
{
    /**
     * Reports whether a PHPStan error should be omitted.
     */
    public function shouldIgnore(Error $error, Node $node, Scope $scope): bool;
}
