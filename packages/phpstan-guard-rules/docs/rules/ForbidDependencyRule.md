# ForbidDependencyRule

| Property | Value |
|----------|-------|
| Identifier | `customRules.forbiddenDependency` |
| Scope | Symbol references and file access in analyzed PHP files |
| Configurable | Yes (`forbiddenDependencies`, `dependencyProjectRoot`, `dependencyFileReaders`) |

## Default Policy

Each project-root directory is a dependency boundary. Code may depend on files
inside its own root directory and inside `src/`, but must not reuse files from
other root directories. This applies to both PHP symbols and file-reading APIs.
New directories receive the same boundary without enumerating them in config.

| Source | Allowed project directories |
|--------|-----------------------------|
| `src/**` | `src/**` |
| `tests/**` | `tests/**`, `src/**` |
| `examples/**` | `examples/**`, `src/**` |
| `bench/**` | `bench/**`, `src/**` |
| Any other root directory | That directory and `src/**` |

The boundary uses the first path segment, so `tests/Unit` may reference
`tests/Integration`. `example/` and `examples/` are separate boundaries and cannot
reference each other. `fixtures/`, `config/`, and `resources/` are also separate
boundaries. Test inputs belong inside `tests/`; do not create a separate root
directory and exempt it from the boundary to share test-only code or data.

`vendor/` is exempt as both a source and destination so Composer library use is
permitted. Files directly at the project root, such as `composer.json` and
`bootstrap.php`, are outside this directory boundary in both directions. Paths
outside the configured project are also outside the policy's scope. These are
project architecture checks, not a filesystem sandbox.

The distributed configuration is:

```neon
parameters:
    toolkit:
        dependencyProjectRoot: %currentWorkingDirectory%
        forbiddenDependencies:
            -
                from: ['*/**']
                excludeFrom: ['vendor/**']
                to: ['*/**']
                excludeTo: ['src/**', 'vendor/**']
                allowSameRootDirectory: true
```

Run PHPStan from the project root, or set `dependencyProjectRoot` to the absolute
project directory when invoking PHPStan from another directory. Paths do not
infer their root from namespace names, nested Composer files, or arbitrary
occurrences of a directory name in a filename.

## Custom Boundaries

Each policy has `from` and `to` lists of root-relative glob patterns. Optional
`excludeFrom` and `excludeTo` lists exempt sources and destinations respectively.
`allowSameRootDirectory: true` also exempts references within the same project-root
directory; its default is `false` for custom policies.

A dependency is forbidden if its source matches `from`, its destination matches
`to`, and none of that policy's exemptions apply. Exemptions affect only their
own policy. Overlapping policies produce one finding per destination at a
reference node.

For example, add a narrower restriction within `src/`:

```neon
parameters:
    toolkit:
        forbiddenDependencies:
            -
                from: ['src/Public/**']
                to: ['src/Internal/**']
```

PHPStan merges list configuration, so this adds to the root-directory boundary.
Appending an exemption does not relax another policy. Use
`forbiddenDependencies!:` when deliberately replacing the entire list, or
`forbiddenDependencies!: []` for an empty policy list.

Matching is case-sensitive and anchored at the project root. `*` matches within
one path segment, `**` crosses directory boundaries, `**/` also matches zero
directories, and `?` matches one non-separator character. Separators and `.`/`..`
segments are normalized; existing symlink destinations are also checked. A
symlink crossing root directories does not receive the same-directory exemption.

## Symbol Dependencies

The rule checks the declaration file PHPStan resolves for:

- class instantiation, static calls, class constants, enum cases, and `::class`;
- inheritance, interfaces, trait use, attributes, and `instanceof`;
- native parameter, return, property, and catch types;
- function calls and global constants, including imported aliases;
- methods and properties with a statically known receiver; and
- dynamic class references when PHPStan knows one constant class name.

Method dependencies use the method's declaring class, including inherited
methods. Unused imports, PHPDoc-only type references, and transitive dependencies
through an otherwise allowed declaration are not independently traversed.

PHPStan must be able to discover the referenced declarations. Composer autoload
metadata, analyzed paths, and `scanDirectories`/`scanFiles` all work. When examples
are excluded from analysis and are not autoloaded, add the existing directories
to `scanDirectories`:

```neon
parameters:
    scanDirectories:
        - examples
```

This discovers symbols without executing the example or analyzing its body.
Include `tests/` in PHPStan's analyzed paths to check test dependencies. An
unknown symbol remains subject to PHPStan's own unknown-symbol diagnostics.

## File Dependencies

All four `include`/`require` forms are checked. Built-in reader registrations
cover `file_get_contents`, `fopen`, `file`, `readfile`, `parse_ini_file`,
`simplexml_load_file`, `hash_file`, `md5_file`, `sha1_file`, and
`SplFileObject::__construct`. Opening a file counts as a dependency regardless
of the requested mode.

Only the registered path argument is checked. Imported function aliases and
reordered named arguments are resolved. A namespaced function merely named
`file_get_contents` does not inherit the built-in registration.

```php
require __DIR__ . '/../examples/bootstrap.php'; // Forbidden from tests/.
file_get_contents(dirname(__DIR__) . '/examples/input.json'); // Forbidden.
file_get_contents(filename: __DIR__ . '/../examples/input.json'); // Forbidden.

echo 'See examples/input.json'; // An explanation, not a file dependency.
file_get_contents(__DIR__ . '/data/input.json'); // Same tests/ directory: allowed.
file_get_contents(__DIR__ . '/../fixtures/input.json'); // Forbidden: keep test inputs inside tests/.
```

The path evaluator handles string literals, `__DIR__`, `__FILE__`, concatenation,
built-in `dirname()` with constant arguments, and expressions PHPStan resolves
to one constant string. Absolute local paths and `file:///` paths are supported.
No project code is executed to resolve a path.

Bare relative paths such as `examples/input.json` depend on the runtime working
directory and potentially `include_path`; the rule does not assume that either
equals the analysis project root. Remote URLs, other stream wrappers, unpacked
path arguments, uncertain unions, and paths or call targets only known at runtime
are left unresolved. Variables containing generalized strings, including some
paths assigned from `__DIR__`, may consequently remain unresolved. Use a directly
resolvable path expression when this boundary must be checked.

## Registering File Readers

Add fully qualified function or `Class::method` names, with a zero-based argument
`position` and its named-argument `name`:

```neon
parameters:
    toolkit:
        dependencyFileReaders:
            'App\Config\Loader::load':
                position: 0
                name: path
            'App\readConfig':
                position: 1
                name: filename
```

Registrations support functions, static and instance methods, and constructors
using `Class::__construct`. Inherited methods use their declaring class name.
Names are case-insensitive. These entries extend the built-ins or override the
argument definition of an existing reader. Arbitrary string arguments are never
interpreted as filesystem paths.

## Diagnostics and Disabling

Each error names the source file, destination file, symbol or reader, and matched
target pattern, followed by an instruction to remove the dependency or move the
shared responsibility to an allowed location. A file reference includes non-PHP
data such as JSON fixtures.

The rule follows `toolkit.allRules`, which defaults to `true`. Guard's existing
`phpstan.all-rules` policy checks that shared switch. Disable this rule explicitly
with:

```neon
parameters:
    toolkit:
        forbidDependency:
            enabled: false
```
