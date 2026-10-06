<?php

declare(strict_types=1);

namespace Example\Guard;

use Guard\Collect\Input;
use Guard\Collect\InputSet;
use Guard\Collect\Selection;
use Guard\Collect\StructuredFile;
use Guard\Document\XmlDocument;
use Guard\Execution\Context;
use Guard\Execution\Plan;
use Guard\Policy\Policy;
use Guard\Policy\PolicyException;
use Guard\Reporting\Finding;
use Guard\Structure\ParsedDocument;

/** Checks already-collected XML against an already-collected XSD; performs no file I/O. */
final class XmlSchemaPolicy implements Policy
{
    /**
     * @param list<string> $files
     */
    public function __construct(private array $files, private string $schema)
    {
    }

    public function inputs(Context $context): array
    {
        return [
            'documents' => new Input(new Selection('patterns', $this->files, [], '', true), 'xml'),
            'schema' => new Input(new Selection('files', [$this->schema], [], '', true), 'xml'),
        ];
    }

    public function evaluate(InputSet $inputs, Context $context): Plan
    {
        if ($inputs->get('documents')->files === []) {
            return new Plan([], []);
        }
        $schemaFile = $inputs->get('schema')->files[$this->schema] ?? null;
        if ($schemaFile === null) {
            throw new PolicyException('Schema "' . $this->schema . '" is outside the collector scope. Include it in collect.include and remove any matching collect.exclude entry.');
        }
        $schema = $this->xml($schemaFile);
        $validator = new SchemaValidation($schema, $this->schema);
        $findings = [];
        foreach ($inputs->get('documents')->files as $path => $file) {
            foreach ($validator->errors($this->xml($file)) as $error) {
                $findings[] = new Finding(
                    $path,
                    'example.xml-schema',
                    'required',
                    'XML does not conform to "' . $this->schema . '": ' . $error . ' Update the XML to satisfy this schema.'
                );
            }
        }
        return new Plan($findings, [], $findings);
    }

    private function xml(StructuredFile $file): XmlDocument
    {
        $path = $file->file->path;
        try {
            $value = $file->value();
        } catch (PolicyException $exception) {
            throw new PolicyException($path . ': ' . $exception->getMessage(), 0, $exception);
        }
        $document = $value instanceof ParsedDocument ? $value->copy() : null;
        if (!$document instanceof XmlDocument) {
            throw new PolicyException('Cannot read XML "' . $path . '". Provide an existing readable XML file and the built-in xml structurer.');
        }
        return $document;
    }
}
