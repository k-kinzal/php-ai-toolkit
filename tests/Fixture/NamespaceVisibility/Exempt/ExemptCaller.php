<?php

declare(strict_types=1);

namespace Tests\Fixture\NamespaceVisibility\Exempt;

use Tests\Fixture\NamespaceVisibility\Package\NamespaceScoped;

final class ExemptCaller
{
    public function useScopedClass(): int
    {
        $scoped = new NamespaceScoped();

        return $scoped->run() + NamespaceScoped::LIMIT;
    }
}
