<?php

declare(strict_types=1);

namespace Guard\Extension;

/**
 * Registers collectors and their information-to-policy bindings.
 */
interface Extension
{
    /**
     * Adds this extension to the same registry used by the built-in policies.
     */
    public function register(Registry $registry): void;
}
