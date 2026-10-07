<?php

declare(strict_types=1);

namespace Guard\Extension;

use Guard\Config\Configuration;
use Guard\Structure\DocumentStructurer;
use Guard\Structure\Markdown\Badge\BadgeStructurer;
use Guard\Structure\Markdown\HeadingStructurer;
use Guard\Structure\Php\MetricParser;
use Guard\Structure\Php\TokenParser;
use Guard\Structure\TextStructurer;

/**
 * Registers built-in formats and the same policy declarations available to extensions.
 */
final class BuiltinExtension implements Extension
{
    /**
     * Creates the BuiltinExtension with its declared dependencies.
     */
    public function __construct(private Configuration $configuration)
    {
    }
    /**
     * Adds all structure producers; only requested structures will run.
     */
    public function register(Registry $registry): void
    {
        $registry->addStructure('php.tokens', new TokenParser());
        $registry->addStructure('php.metrics', new MetricParser());
        $registry->addStructure('markdown.headings', new HeadingStructurer());
        $registry->addStructure('markdown.badges', new BadgeStructurer());
        $registry->addStructure('text', new TextStructurer());
        foreach (['json', 'json5', 'yaml', 'yml', 'neon', 'toml', 'xml', 'php'] as $format) {
            $registry->addStructure($format, new DocumentStructurer($format));
        }
        foreach ($this->configuration->policies as $binding) {
            $registry->addPolicy($binding->id, $binding->policy, $binding->reportOrder);
        }
    }
}
