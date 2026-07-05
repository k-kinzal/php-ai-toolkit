# NoNonPublicMethodRule

| Property | Value |
|----------|-------|
| Identifier | `customRules.nonPublicMethod` |
| Scope | All class-like methods |
| Configurable | No |

## What It Detects

Reports private methods everywhere, and protected methods except in abstract classes, traits, and override methods.

```php
final class InvoiceService
{
    public function issue(Invoice $invoice): Receipt
    {
        $this->validate($invoice);
        return $this->createReceipt($invoice);
    }

    // ERROR: Private method validate() is prohibited.
    private function validate(Invoice $invoice): void
    {
        // ...
    }
}
```

Protected methods are allowed when they are intentionally part of an inheritance boundary:

```php
abstract class ImportTemplate
{
    public function import(File $file): Result
    {
        return $this->parse($file);
    }

    protected function parse(File $file): Result
    {
        // ...
    }
}
```

## Why This Is an Error

Private and non-override protected methods often hide additional responsibilities inside a class. The rule is not a visibility preference and should not be fixed by changing method access modifiers. If a behavior is complex enough to need its own named method, that behavior usually deserves one of these outcomes:

1. **It belongs to the type**: Make it part of the public behavior and test it through that public API.

2. **It is a separate responsibility**: Extract it to a focused collaborator with its own public API.

3. **It is an inheritance extension point**: Keep it protected only on an abstract class, trait, or override method.

## How to Fix

Extract hidden behavior to a focused collaborator:

```php
final class InvoiceService
{
    public function __construct(
        private readonly InvoiceValidator $validator,
    ) {
    }

    public function issue(Invoice $invoice): Receipt
    {
        $this->validator->validate($invoice);
        return Receipt::fromInvoice($invoice);
    }
}

final class InvoiceValidator
{
    public function validate(Invoice $invoice): void
    {
        // ...
    }
}
```

## Configuration

This rule is registered by `extension.neon`.
