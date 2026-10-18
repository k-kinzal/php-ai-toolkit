<?php

declare(strict_types=1);

namespace DecisionsLint\Provider\OpenAi;

/**
 * Lays the named sections of a subject out as one text document for an API that takes plain input.
 */
final class InputRenderer
{
    /**
     * Returns the sections as Markdown headings followed by their text, source sections fenced as PHP.
     *
     * @param array<string, string> $subject
     */
    public function render(array $subject): string
    {
        $blocks = [];
        foreach ($subject as $name => $text) {
            $heading = '## ' . str_replace('_', ' ', $name);
            $blocks[] = str_ends_with($name, 'source') || $name === 'declaration'
                ? $heading . "\n\n```php\n" . rtrim($text, "\n") . "\n```"
                : $heading . "\n\n" . $text;
        }

        return implode("\n\n", $blocks) . "\n";
    }
}
