<?php

declare(strict_types=1);

namespace Toolkit\Guard\Policy;

use RuntimeException;

/**
 * A malformed policy or a document that cannot be safely inspected or changed.
 */
final class PolicyException extends RuntimeException
{
}
