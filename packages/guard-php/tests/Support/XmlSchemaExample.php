<?php

declare(strict_types=1);

namespace Tests\Support;

/** Loads the external example without adding it to Guard's production autoload map. */
final class XmlSchemaExample
{
    public static function load(): void
    {
        foreach (['SchemaValidation', 'XmlSchemaPolicy', 'XmlSchemaExtension'] as $class) {
            require_once dirname(__DIR__, 2) . '/examples/xml-schema/src/' . $class . '.php';
        }
    }

    public static function schema(): string
    {
        return '<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema"><xs:element name="count" type="xs:positiveInteger"/></xs:schema>';
    }

    public static function configuration(): string
    {
        return "version: 1\ncollect: {exclude: ['vendor/**']}\nextensions:\n  Example\\Guard\\XmlSchemaExtension:\n    schema: schema.xsd\n";
    }
}
