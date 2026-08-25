<?php

declare(strict_types=1);

namespace Toolkit\DocGen\Render;

use function json_encode;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

use function mb_strimwidth;

use Toolkit\DocGen\Analysis\Diff\DiffKey;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Render\Diff\DiffHtml;

/**
 * Builds the client-side search index script.
 *
 * The index is loaded as a plain script tag so search also works when the
 * generated site is opened directly from the local filesystem.
 */
final class SearchIndexBuilder
{
    /** @readonly */
    private SiteUrl $url;

    /**
     * Creates a search index builder.
     */
    public function __construct(?SiteUrl $url = null)
    {
        $this->url = $url ?? new SiteUrl();
    }

    /**
     * Builds the search index JavaScript for one project model.
     *
     * Entries are encoded one at a time and appended to the output, so a
     * large dependency tree never holds every entry as an array and as
     * encoded JSON at the same time.
     *
     * @param ?DiffHtml $diff the comparison the site displays, if any
     */
    public function build(ProjectModel $model, ?DiffHtml $diff = null): string
    {
        $diff ??= new DiffHtml();
        $json = '';
        $separator = '';
        foreach ($model->classLikes as $classLike) {
            if ($classLike->isDev) {
                continue;
            }

            $page = $this->url->classLikePage($classLike);
            $summary = $classLike->docBlock !== null ? $classLike->docBlock->summary : '';
            $status = $diff->isActive() ? $diff->classLikeStatus($classLike->fqcn) : null;
            $json .= $separator . $this->encode($this->item($classLike->shortName, $classLike->fqcn, $classLike->kind, $page, $summary, $status));
            $separator = ',';
            foreach ($this->memberItems($classLike, $page, $diff) as $memberItem) {
                $json .= $separator . $this->encode($memberItem);
            }
        }

        foreach ($model->functions as $function) {
            if (!$function->isDev) {
                $json .= $separator . $this->encode($this->item(
                    $function->shortName,
                    $function->fqn . '()',
                    'function',
                    $this->url->functionPage($function),
                    $function->docBlock !== null ? $function->docBlock->summary : '',
                    $diff->isActive() ? $diff->statusOf($diff->functionKey($function->fqn)) : null,
                ));
                $separator = ',';
            }
        }

        foreach ($model->documents as $document) {
            $json .= $separator . $this->encode($this->item(
                $document->title,
                $document->packageName . '/' . $document->path,
                'document',
                $this->url->documentPage($document->packageName, $document->path),
                $document->path,
                $diff->isActive() ? $diff->documentStatus($document->packageName, $document->path) : null,
            ));
            $separator = ',';
        }

        return 'window.__DOCGEN_INDEX__=[' . $json . '];' . "\n";
    }

    /**
     * Encodes one index entry as JSON.
     *
     * @param array{n: string, f: string, k: string, u: string, s: string, d?: string} $item
     */
    public function encode(array $item): string
    {
        $json = json_encode($item, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $json === false ? '{}' : $json;
    }

    /**
     * Builds the index entries of one class-like symbol's members.
     *
     * @param ?DiffHtml $diff the comparison the site displays, if any
     *
     * @return list<array{n: string, f: string, k: string, u: string, s: string, d?: string}>
     */
    public function memberItems(\Toolkit\DocGen\Analysis\Model\ClassLikeDoc $classLike, string $page, ?DiffHtml $diff = null): array
    {
        $diff ??= new DiffHtml();
        $items = [];
        foreach ($classLike->methods as $method) {
            if ($method->visibility === 'private') {
                continue;
            }

            $items[] = $this->item(
                $classLike->shortName . '::' . $method->name,
                $classLike->fqcn . '::' . $method->name . '()',
                'method',
                $page . '#method.' . $method->name,
                $method->docBlock !== null ? $method->docBlock->summary : '',
                $diff->isActive() ? $diff->statusOf($diff->methodKey($classLike->fqcn, $method->name)) : null,
            );
        }

        foreach ($classLike->constants as $constant) {
            if ($constant->visibility !== 'private') {
                $items[] = $this->item(
                    $classLike->shortName . '::' . $constant->name,
                    $classLike->fqcn . '::' . $constant->name,
                    'constant',
                    $page . '#constant.' . $constant->name,
                    $constant->docBlock !== null ? $constant->docBlock->summary : '',
                    $diff->isActive() ? $diff->memberStatus($classLike->fqcn, DiffKey::CONSTANT, $constant->name) : null,
                );
            }
        }

        return $items;
    }

    /**
     * Builds one compact index entry.
     *
     * @param ?string $status the diff state of the entry, outside a plain site
     *
     * @return array{n: string, f: string, k: string, u: string, s: string, d?: string}
     */
    public function item(string $name, string $fullName, string $kind, string $urlPath, string $summary, ?string $status = null): array
    {
        $item = [
            'n' => $name,
            'f' => $fullName,
            'k' => $kind,
            'u' => $urlPath,
            's' => mb_strimwidth($summary, 0, 120, '…'),
        ];

        return $status === null ? $item : $item + ['d' => $status];
    }
}
