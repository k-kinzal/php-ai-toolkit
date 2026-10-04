# ForbidFileTermRule

| Property | Value |
|----------|-------|
| Identifier | `customRules.forbiddenFileTerm` |
| Scope | All analyzed PHP files matching a configured path |
| Configurable | Yes (`forbiddenTermsByPath`) |

## Configuration

Map each restricted file path pattern to the literal terms that cannot belong
in that part of the design:

```neon
parameters:
    toolkit:
        forbiddenTermsByPath:
            'src/Query/Abstraction/*':
                - mysql
                - postgres
                - sqlite
```

The default map is empty, so a project must declare its own architectural
vocabulary boundaries. Path patterns use the same normalized `fnmatch`
matching as the toolkit's other path-based PHPStan rules. A relative pattern
matches the corresponding suffix of PHPStan's absolute analyzed file path.

## What It Detects

The rule reads every line of a matching PHP file and checks its complete source
text. A configured term is therefore forbidden in all of these locations:

- identifiers and type names;
- string literals;
- comments and PHPDoc; and
- any other source text on the line.

For this configuration:

```neon
forbiddenTermsByPath:
    'src/Query/Abstraction/*':
        - mysql
        - postgres
        - sqlite
```

all of these are reported inside `src/Query/Abstraction/`:

```php
/** PostgreSQL-specific behavior. */
final class AbstractQuery
{
    public const DRIVER = 'MYSQL';

    public function sqliteQuery(): string
    {
        return '...';
    }
}
```

Matching is case-insensitive literal substring matching. It deliberately has no
word boundary, so `mysql` finds `MySqlCompiler`, and `postgres` finds both
`Postgres` and `PostgreSQL`. Regular expressions are not interpreted. Empty
terms and case-insensitive duplicate terms in one path policy are ignored.

When overlapping path policies contain the same term, the rule reports that
term only once on each affected line. The same forbidden term on another line
is a separate, precisely located violation.

## Why This Is an Error

A term that names a concept outside a layer's responsibility is evidence that
the responsibility itself has leaked across the architectural boundary. For
example, a database-independent query abstraction cannot contain branches,
types, constants, comments, or documentation for individual database engines.

Removing or disguising the word would leave the misplaced behavior intact. The
rule therefore reports the occurrence as a design error and directs the author
to redesign the responsibility boundary.

## How to Fix

Move the concept and all behavior depending on it to the concrete layer that
owns it. Keep only backend-independent contracts and behavior in the restricted
abstraction.

For example, replace a backend branch in an abstraction:

```php
if ($driver === 'mysql') {
    return $this->compileMysqlLimit($limit);
}
```

with a backend-neutral contract implemented by the appropriate concrete
adapter. Do not satisfy the rule by renaming `mysql`, abbreviating it, building
the word dynamically, or deleting a comment while retaining the branch.

The reported error states this explicitly:

```text
Forbidden term "mysql" appears in a file matched by path
"src/Query/Abstraction/*"; this is a design error because the concept does not
belong in this layer. Redesign the responsibility boundary and move the concept
and its behavior to the appropriate layer. Renaming, abbreviating, or deleting
only the term is not a fix.
```

Only PHP files included in PHPStan's analyzed paths are checked. Use a separate
text-oriented check when the same policy must cover Markdown, YAML, JSON, or
other non-PHP files.
