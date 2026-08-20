<?php

declare(strict_types=1);

/**
 * @visibility parent
 */
final class NamespaceVisibilityGlobalScoped
{
    /**
     * @visibility root
     */
    public function rootScoped(): int
    {
        return 1;
    }

    /**
     * @visibility namespace
     */
    public function namespaceScoped(): int
    {
        return 2;
    }
}
