<?php

declare(strict_types=1);

namespace Guard\Extension;

use Guard\Collect\Configuration\ConfigurationCollector;
use Guard\Collect\Configuration\ConfigurationDocument;
use Guard\Collect\Markdown\MarkdownCollector;
use Guard\Collect\Markdown\MarkdownDocuments;
use Guard\Collect\Php\PhpCollector;
use Guard\Collect\Php\PhpSources;
use Guard\Collect\Tree\DirectoryTree;
use Guard\Collect\Tree\TreeCollector;
use Guard\Policy\Configuration\ConfigurationPolicy;
use Guard\Policy\Doc\DocPolicy;
use Guard\Policy\Loc\LocPolicy;
use Guard\Policy\Tree\TreePolicy;

/**
 * Registers the shipped policies through the public extension contract.
 */
final class BuiltinExtension implements Extension
{
    /**
     * Preserves source finding order and configuration document discovery order.
     */
    public function register(Registry $registry): void
    {
        $registry->addCollector('configuration', new ConfigurationCollector());
        $registry->addCollector('php', new PhpCollector());
        $registry->addCollector('tree', new TreeCollector());
        $registry->addCollector('markdown', new MarkdownCollector());
        $registry->addPolicy('loc', PhpSources::class, new LocPolicy());
        $registry->addPolicy('tree', DirectoryTree::class, new TreePolicy());
        $registry->addPolicy('doc', MarkdownDocuments::class, new DocPolicy());
        $registry->addPolicy('configuration', ConfigurationDocument::class, new ConfigurationPolicy());
    }
}
