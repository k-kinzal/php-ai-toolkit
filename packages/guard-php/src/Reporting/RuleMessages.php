<?php

declare(strict_types=1);

namespace Guard\Reporting;

/**
 * Describes what each built-in rule rejects and the edit that resolves it.
 */
final class RuleMessages
{
    /**
     * Diagnostic descriptions for structure and documentation rule identifiers.
     */
    private const MESSAGES = [
        'structure.max_files' => 'The directory contains more files than allowed. Group related files into permitted subdirectories or split the subtree until the configured count is met.',
        'structure.max_total_files' => 'The subtree contains more files than allowed. Move independent groups outside this subtree or merge related files until the total meets the limit; adding subdirectories inside it does not reduce the total.',
        'structure.max_dirs' => 'The directory contains more subdirectories than allowed. Merge related subdirectories or flatten the structure until the configured count is met.',
        'structure.max_depth' => 'A directory is nested deeper than allowed. Move its files and directories to a shallower permitted location.',
        'structure.disallowed_file' => 'A file name does not match any allowed pattern. Rename or move the file to a permitted location; an empty allow list permits no direct files.',
        'structure.denied_file' => 'A file name matches a denied pattern. Rename or move the file, or delete it if it is unnecessary.',
        'structure.disallowed_dir' => 'A directory name does not match any allowed pattern. Rename or move the directory to a permitted location.',
        'structure.denied_dir' => 'A directory name matches a denied pattern. Rename or remove that directory and move any needed contents to a permitted location.',
        'structure.missing_required_file' => 'A required file is missing. Create the named file in the directory matched by the rule.',
        'structure.empty_directory' => 'An empty directory is forbidden. Remove the directory or add its intended contents.',
        'structure.file_case' => 'A file name violates the configured case convention. Rename the file to match that convention and update references to it.',
        'structure.dir_case' => 'A directory name violates the configured case convention. Rename the directory to match that convention and update references to it.',
        'documentation.missing_document' => 'A declared document is missing. Restore it at its configured path with the declared content and structure.',
        'documentation.undeclared_document' => 'A scanned document is not declared. Move its content into an appropriate section of a declared document and delete the undeclared file.',
        'documentation.unexpected_heading' => 'A heading is not declared. Remove the extra heading and move its content into an existing declared section.',
        'documentation.missing_heading' => 'A declared heading is missing. Restore the heading at its declared position.',
        'documentation.renamed_heading' => 'A heading has been renamed. Restore its declared text and keep the section content.',
        'documentation.changed_heading_level' => 'A heading has the wrong nesting level. Change its Markdown heading markers to the declared level.',
        'documentation.moved_heading' => 'A heading is out of order. Move the complete section to its declared position.',
        'documentation.unexpected_outline_heading' => 'A heading does not match any permitted outline at its position. Rename, move or remove it to follow one declared outline.',
        'documentation.missing_outline_heading' => 'A required outline heading is missing. Add a permitted heading and its section at the declared position.',
        'documentation.missing_badges' => 'The declared badge block is missing or misplaced. Put the badges below the level-1 title in the declared order, separated from the title by one blank line.',
        'documentation.unexpected_badge' => 'A badge is undeclared, repeated or out of order. Remove undeclared or duplicate badges and restore the declared order.',
        'documentation.missing_badge' => 'A required badge is missing. Add a badge with the declared image URL and label at its declared position.',
        'documentation.unexpected_content' => 'The document differs from the configured exact content. Replace its contents with the declared text, preserving the specified final newline.',
    ];

    /**
     * Returns a diagnostic description for rule listings, independent of current violations.
     */
    public function forRule(string $id): string
    {
        if (str_starts_with($id, 'metrics.')) {
            return $this->diagnostic($id, str_contains($id, 'complexity')
                ? 'The function or method exceeds its configured cyclomatic complexity limit. Simplify conditional branches and extract independent operations into focused methods until each stays within the limit.'
                : 'The source unit exceeds its configured line limit. Split unrelated responsibilities into smaller files, classes or methods until each stays within the limit; preserve behavior and tests.');
        }
        return $this->diagnostic($id, self::MESSAGES[$id] ?? 'This custom rule needs a diagnostic message. Describe the offending input and the concrete edit that resolves it in the rule definition.');
    }

    /**
     * Includes the purpose of a constraint alongside its specific problem and repair instructions.
     */
    public function diagnostic(string $id, string $message): string
    {
        $reason = $this->rationale($id);
        return $message . ($reason === '' ? '' : ' ' . $reason);
    }

    /**
     * Explains the engineering consequence rather than merely repeating a configured limit.
     */
    public function rationale(string $id): string
    {
        if (str_starts_with($id, 'metrics.')) {
            return str_contains($id, 'complexity')
                ? 'Too many independent execution paths make behavior harder to reason about and test.'
                : 'Oversized source units hide unrelated responsibilities and make changes harder to review.';
        }
        if (str_starts_with($id, 'documentation.')) {
            return str_contains($id, 'badge')
                ? 'A consistent badge block keeps project status links discoverable and avoids misleading duplicates.'
                : 'The declared document structure keeps information discoverable and prevents required project guidance from being lost or fragmented.';
        }
        return match ($id) {
            'structure.max_files', 'structure.max_total_files', 'structure.max_dirs' => 'Oversized directories make related files and their ownership harder to locate.',
            'structure.max_depth' => 'Deep directory nesting makes navigation and dependency boundaries harder to follow.',
            'structure.disallowed_file', 'structure.denied_file', 'structure.disallowed_dir', 'structure.denied_dir' => 'The declared directory layout keeps files within their intended architectural boundaries.',
            'structure.file_case', 'structure.dir_case' => 'Consistent names make paths predictable and reduce mistakes when referring to files across platforms.',
            'structure.missing_required_file' => 'Required files provide the entry points or project guidance expected at this location.',
            'structure.empty_directory' => 'Empty directories imply a structure without an implementation and are not retained by Git.',
            default => '',
        };
    }
}
