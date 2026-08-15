<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Render;

use function json_encode;

use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

use function mb_strimwidth;

use PhpAiToolkit\DocGen\Analysis\ProjectModel;

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
     */
    public function build(ProjectModel $model): string
    {
        $json = '';
        $separator = '';
        foreach ($model->classLikes as $classLike) {
            if ($classLike->isDev) {
                continue;
            }

            $page = $this->url->classLikePage($classLike);
            $json .= $separator . $this->encode($this->item($classLike->shortName, $classLike->fqcn, $classLike->kind, $page, $classLike->docBlock !== null ? $classLike->docBlock->summary : ''));
            $separator = ',';
            foreach ($this->memberItems($classLike, $page) as $memberItem) {
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
                ));
                $separator = ',';
            }
        }

        return 'window.__DOCGEN_INDEX__=[' . $json . '];' . "\n";
    }

    /**
     * Encodes one index entry as JSON.
     *
     * @param array{n: string, f: string, k: string, u: string, s: string} $item
     */
    public function encode(array $item): string
    {
        $json = json_encode($item, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $json === false ? '{}' : $json;
    }

    /**
     * Builds the index entries of one class-like symbol's members.
     *
     * @return list<array{n: string, f: string, k: string, u: string, s: string}>
     */
    public function memberItems(\PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc $classLike, string $page): array
    {
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
                );
            }
        }

        return $items;
    }

    /**
     * Builds one compact index entry.
     *
     * @return array{n: string, f: string, k: string, u: string, s: string}
     */
    public function item(string $name, string $fullName, string $kind, string $urlPath, string $summary): array
    {
        return [
            'n' => $name,
            'f' => $fullName,
            'k' => $kind,
            'u' => $urlPath,
            's' => mb_strimwidth($summary, 0, 120, '…'),
        ];
    }
}
