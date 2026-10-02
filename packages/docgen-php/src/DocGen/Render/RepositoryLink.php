<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Render;

use function is_string;
use function parse_url;
use function preg_replace;
use function sprintf;

/**
 * Renders the links that lead from the site back to the code it documents.
 *
 * A generated site is the read side of a repository, so every page carries
 * the way back to it, and a package names the repository it is published
 * from. The link is written with the address it leads to rather than with a
 * word for it: a reader who is about to leave the site is told where to.
 */
final class RepositoryLink
{
    /**
     * Renders the link the page shell carries, or nothing without one.
     *
     * The shell has room for the host alone, which is what says that the
     * link leaves the site and where it lands.
     */
    public function topbar(RenderKit $services): string
    {
        $url = $services->model->repository;
        if ($url === null) {
            return '';
        }

        return $this->link($services, $url, $this->host($url)) . "\n";
    }

    /**
     * Renders the link a page names one repository in full with.
     */
    public function full(RenderKit $services, string $url): string
    {
        return $this->link($services, $url, $this->label($url));
    }

    /**
     * Renders one link out of the site to a repository.
     *
     * @param string $text how the address is written on the page
     */
    public function link(RenderKit $services, string $url, string $text): string
    {
        $escaper = $services->escaper;

        return sprintf(
            '<a class="repo-link" href="%s" title="Repository: %s" rel="noreferrer">%s</a>',
            $escaper->e($url),
            $escaper->e($url),
            $escaper->e($text),
        );
    }

    /**
     * Returns the host one repository address is served by.
     *
     * An address without a readable host is written out as it stands, which
     * is longer than a host but never wrong.
     */
    public function host(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : $url;
    }

    /**
     * Returns how one repository address is written where there is room.
     *
     * The scheme is dropped because it tells a reader nothing about where
     * the link goes, while the host and the path together are the name the
     * repository is known by.
     */
    public function label(string $url): string
    {
        return preg_replace('#^https?://(www\.)?#', '', $url) ?? $url;
    }
}
