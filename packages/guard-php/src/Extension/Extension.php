<?php

declare(strict_types=1);

namespace Guard\Extension;

/**
 * Registers structure producers and policies.
 */
interface Extension
{
    /**
     * Adds this extension to the same registry used by the built-in policies.
     */
    public function register(Registry $registry): void;
}
