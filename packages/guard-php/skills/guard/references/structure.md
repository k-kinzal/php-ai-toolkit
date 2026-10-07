# Directory structure

The `structure` section constrains what each directory may contain, how entries are named and how many there are. Every finding is required and none is repaired; Guard does not move or rename files. The semantics are in [the structure reference](../../../docs/structure.md).

## Resolving findings

A structure finding says that a new entry does not fit where it was put. Find the place the project already has for it, or the concept the entries share, before moving anything.

| Rule ID | Practice |
|---------|----------|
| `structure.denied_file` | The name is denied because it names no responsibility, such as `*Helper.php`, `*Util.php`, `*Manager.php` or `*Service.php`. Move each function to the class that owns the data it works on, or extract a class named after what it does, such as `PriceRounder`. Synonyms such as `*Tools.php` or `*Support.php` are the same violation under another name. |
| `structure.disallowed_file` | The directory does not hold that kind of file. Put it where the project keeps that kind: resources and templates outside `src`, tests under the test tree, documentation in a declared document. A directory with `allow: []`, such as `src`, holds only directories. |
| `structure.max_files`, `structure.max_dirs`, `structure.max_total_files` | The namespace has grown past one concept. Group the entries by the concept they share into subdirectories named after it, such as `Parser/` and `Printer/`, and update namespaces and references. Do not split by count or by letter, and do not create `Misc/`, `Common/` or `Other/`. |
| `structure.max_depth` | Flatten the nesting. A deep path usually repeats a concept that one level already names. |
| `structure.denied_dir`, `structure.disallowed_dir` | Use the place the project has for that content. A denied `scripts/` means Composer scripts or `bin/`; a denied project-root `docs/` means a declared document such as `README.md` holds it. |
| `structure.missing_required_file` | Add the named file with real content, such as `SKILL.md` in a skill directory. |
| `structure.empty_directory` | Remove the directory, or add the file it was created for in the same change. Do not add a placeholder such as `.gitkeep`. |
| `structure.file_case`, `structure.dir_case` | Rename to the convention. For PHP under PSR-4, rename the class, its namespace segment and every reference with it, then run `composer dump-autoload` and the tests. |

After a move, run the tests, PHPStan and Deptrac: a move across namespaces can break autoloading or an architecture layer.

### Changes that hide a finding instead of fixing it

- Renaming a file so that the pattern stops matching while the content stays the same, such as `StringHelper.php` to `StringHelpers2.php` or `scripts/` to `tools/`.
- Changing the extension, such as `.php` to `.inc`, to escape `allow: ['*.php']`.
- Moving files into an excluded directory such as `build/` or a dotted directory.
- Merging unrelated classes into one file to lower a file count; that moves the finding to the metrics.
- Adding a path to `structure.exclude` or `collect.exclude`, or editing a rule in `guard.yaml`.

## Writing rules

- Scan from `.` so that rules reach the root, dotted directories such as `.github`, and directories outside the source roots. Exclude only tool and build output such as `vendor`, `build` and `.git`.
- Patterns are anchored at both ends and `**` also matches zero segments: `src/**` includes `src` itself, and `PhpStan/Rule` does not match `src/PhpStan/Rule`. `exclude` entries are `fnmatch` globs where `*` crosses `/`.
- Every matching rule applies on its own; rules do not override each other. Write a broad rule such as `src/**` for the common constraints and a narrower rule only for an additional constraint. To relax a broad rule for one directory, narrow the broad rule's `path` instead of adding a rule that cannot undo it.
- Use `allow: []` for a container directory that holds only subdirectories. Leaving `allow` out allows every file.
- Combine `require` and `allow` to make a directory hold exactly one manifest, and `forbid_empty: true` to keep abandoned directories out.
- Deny names that hide a responsibility, such as `*Helper.php`, rather than listing the names that are allowed.

```yaml
structure:
  directories:
    - path: 'modules/*'
      require: [module.yaml]
      allow: [module.yaml]
      dir_case: kebab
      forbid_empty: true
```
