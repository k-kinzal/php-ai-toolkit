<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Cache;

use Closure;
use PhpAiToolkit\DocGen\DocGenException;
use PhpAiToolkit\DocGen\Filesystem\SiteFileWriter;

use function strlen;

/**
 * Writes the pages of a site, skipping the ones already written.
 *
 * A page is rendered only once it is known to be needed, so a run that
 * changed nothing pays for the signature of a page rather than for its
 * HTML. Without a cache every page is rendered, which is the same code
 * path with the question always answered the same way.
 */
final class CachedPageWriter
{
    /** @readonly */
    private SiteFileWriter $writer;

    /** @readonly */
    private ?RenderCache $cache;

    /**
     * Creates a page writer for one generation run.
     */
    public function __construct(?SiteFileWriter $writer = null, ?RenderCache $cache = null)
    {
        $this->writer = $writer ?? new SiteFileWriter();
        $this->cache = $cache;
    }

    /**
     * Writes one page unless the site already holds exactly that page.
     *
     * @param string $signature the digest of everything the page is rendered from
     * @param Closure(): string $render renders the page, called only when it is written
     *
     * @throws DocGenException when the page cannot be written
     */
    public function write(string $outputRoot, string $pagePath, string $signature, Closure $render): PageRecord
    {
        if ($this->cache !== null && $this->cache->isFresh($outputRoot, $pagePath, $signature)) {
            return new PageRecord($pagePath, $signature, $this->cache->sizeOf($pagePath), false);
        }

        $contents = $render();
        $this->writer->write($outputRoot, $pagePath, $contents);

        return new PageRecord($pagePath, $signature, strlen($contents), true);
    }

    /**
     * Collects the pages every worker of one phase reported.
     *
     * A record comes from a worker process, so nothing about it is
     * guaranteed by the type system that produced it. A worker that
     * reported anything else wrote pages the run cannot account for, and a
     * site of unknown completeness is not a site worth remembering.
     *
     * @param list<mixed> $results
     *
     * @return list<PageRecord>
     *
     * @throws DocGenException when a worker reported something else
     */
    public function records(array $results): array
    {
        $records = [];
        foreach ($results as $result) {
            if (!is_array($result)) {
                throw new DocGenException('A documentation worker reported no written pages.');
            }

            foreach ($result as $record) {
                if (!$record instanceof PageRecord) {
                    throw new DocGenException('A documentation worker reported no written pages.');
                }

                $records[] = $record;
            }
        }

        return $records;
    }
}
