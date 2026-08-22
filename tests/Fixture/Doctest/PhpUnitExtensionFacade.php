<?php

declare(strict_types=1);

namespace Tests\Fixture\Doctest;

use PHPUnit\Runner\Extension\Facade;

/**
 * Builds the facade PHPUnit hands to an extension it bootstraps.
 *
 * PHPUnit 13 made Facade an interface and moved the implementation to
 * ExtensionFacade; up to PHPUnit 12 Facade is itself the final class, so it can
 * be neither implemented nor doubled. Only one of the two names exists in any
 * installation, and this is the one place that looks up which — a test naming
 * either directly would fail to analyse on the other line.
 */
final class PhpUnitExtensionFacade
{
    /**
     * Returns an instance of whatever the installed PHPUnit implements the facade with.
     */
    public static function create(): Facade
    {
        $implementation = 'PHPUnit\\Runner\\Extension\\ExtensionFacade';
        if (!class_exists($implementation)) {
            $implementation = Facade::class;
        }

        return new $implementation();
    }
}
