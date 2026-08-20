<?php

declare(strict_types=1);

namespace Tests\Fixture\NamespaceVisibility\Exempt;

use Tests\Fixture\NamespaceVisibility\Package\NamespaceScoped;

/**
 * @visibility parrent
 */
final class ExemptMalformedTags
{
    public function hold(NamespaceScoped $scoped): int
    {
        return $scoped->run();
    }
}
