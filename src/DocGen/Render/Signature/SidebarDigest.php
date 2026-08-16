<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render\Signature;

use function hash;

use PhpAiToolkit\DocGen\Render\Page\SidebarHtml;
use PhpAiToolkit\DocGen\Render\Page\SidebarScope;
use PhpAiToolkit\DocGen\Render\RenderKit;
use PhpAiToolkit\DocGen\Render\SiteUrl;

/**
 * Digests the navigation every page of one scope shares.
 *
 * The digest is the sidebar itself, rendered once for the scope rather
 * than described a second time in another class. Everything the navigation
 * reads — the sibling symbols, the child namespaces, the layers, the
 * documents of the package — is therefore covered by construction, and a
 * navigation that grows a new source of truth cannot quietly fall out of
 * the digest of the pages that show it.
 *
 * The sidebar of a scope is rendered from one fixed page of that scope, so
 * the relative links of the page a digest is asked for never enter it: two
 * pages of one namespace share their navigation, whatever their depth.
 */
final class SidebarDigest
{
    /** @readonly */
    private SidebarHtml $sidebar;

    /** @readonly */
    private SiteUrl $url;

    private ?RenderKit $run = null;

    /** @var array<string, string> */
    private array $digests = [];

    /**
     * Creates a sidebar digest from the navigation renderer.
     */
    public function __construct(?SidebarHtml $sidebar = null, ?SiteUrl $url = null)
    {
        $this->sidebar = $sidebar ?? new SidebarHtml();
        $this->url = $url ?? new SiteUrl();
    }

    /**
     * Returns the digest of the navigation shared by one scope.
     *
     * @param ?string $packageName the package of the scope, or null for the site itself
     * @param ?string $namespace the namespace of the scope, or null for the package itself
     */
    public function of(RenderKit $services, ?string $packageName, ?string $namespace): string
    {
        if ($this->run !== $services) {
            $this->run = $services;
            $this->digests = [];
        }

        $key = ($packageName ?? '') . "\n" . ($namespace ?? "\0");

        return $this->digests[$key] ??= hash('sha256', $this->sidebar->build(
            $services,
            $this->pagePath($packageName, $namespace),
            new SidebarScope($packageName, $namespace, null, []),
        ));
    }

    /**
     * Returns the page the navigation of one scope is rendered from.
     */
    public function pagePath(?string $packageName, ?string $namespace): string
    {
        if ($packageName === null) {
            return 'index.html';
        }

        return $namespace === null
            ? $this->url->packagePage($packageName)
            : $this->url->namespacePage($packageName, $namespace);
    }
}
