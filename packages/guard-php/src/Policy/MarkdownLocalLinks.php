<?php

declare(strict_types=1);

namespace Guard\Policy;

use Guard\Diagnostic\Finding;
use Guard\Diagnostic\PolicyException;
use Guard\Input\Input;
use Guard\Input\InputSet;
use Guard\Input\Selection;
use Guard\Structure\Markdown\Link\LinkList;

/**
 * Rejects local Markdown links whose resolved file or directory is missing.
 */
final class MarkdownLocalLinks implements Policy
{
    /**
     * @param list<string> $paths Markdown file patterns relative to guard.yaml
     * @param list<string> $exclude Markdown source patterns to omit
     */
    public function __construct(private array $paths = ['**/*.md'], private array $exclude = ['**/vendor/**', '**/.git/**'])
    {
    }

    /**
     * @return array<string, Input>
     */
    public function inputs(Context $context): array
    {
        return ['documents' => new Input(new Selection('patterns', $this->paths, $this->exclude), 'markdown.links')];
    }

    /**
     * Reports only references whose metadata was admitted by the collector.
     */
    public function evaluate(InputSet $inputs, Context $context): Plan
    {
        $findings = [];
        foreach ($inputs->get('documents')->files as $file) {
            $links = $file->value();
            if (!$file->readable || !$links instanceof LinkList) {
                throw new PolicyException('Cannot parse Markdown links in ' . $file->file->relativePath . '. Make the file readable and register markdown.links with LinkStructurer.');
            }
            foreach ($links->all() as $link) {
                $target = $file->references[$link['path']] ?? null;
                if ($target !== null && !$target->entry->file && !$target->entry->directory) {
                    $findings[] = new Finding($file->file->relativePath, 'documentation.missing_link_target', 'required', sprintf('Local link "%s" in %s (block starting at line %d) resolves to missing target "%s". Correct the destination or restore that file or directory.', $link['url'], $file->file->relativePath, $link['line'], $target->relativePath));
                }
            }
        }
        return new Plan($findings, []);
    }
}
