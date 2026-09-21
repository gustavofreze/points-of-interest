---
name: php-reusing-tiny-blocks
description: Reuse a tiny-blocks package instead of writing from scratch or adding an outside dependency. Use before building a capability by hand, or when reviewing or refactoring to find an existing package.
---

# Reuse tiny-blocks

Discovers and reuses a tiny-blocks package instead of writing or keeping hand-written code.

Target: the capability sought, named in the request (e.g. `money`).

## When to use

- Before implementing a capability from scratch or adding a dependency from outside the ecosystem.
- When reviewing or refactoring, to find where a package already covers something written by hand.

## When NOT to use

- When the capability is specific to the service domain and not a generic building block.
- When the standard library or a language feature already covers it in a line or two. Do not pull a package for what a
  built-in already does.
- When nothing in the current work needs it yet. Reuse on a real need, do not add a package preventively.

## Rules applied

Baseline `php-code-style`. The chosen library is used per the rule of the layer that consumes it (e.g. domain, driven,
query).

## Assembly order

1. Identify the capability: collections, value objects, money, time, http, mapping, logging, identifiers, and similar.
2. Check `references/catalog.md` for an installed match before searching externally.
3. If there is a match, add it with composer (or confirm it is already in `composer.json`).
4. Read the README and the public API of the installed library from `vendor/`.
5. Use it per the API, without reimplementing.

## Completeness gate

- [ ] Catalog checked before writing from scratch.
- [ ] Match added via composer.
- [ ] Used per the public API as read, not as assumed.
- [ ] On a refactor, the equivalent hand-written code was removed.

## Additional resources

- **`references/catalog.md`**: the generated tiny-blocks table plus the hand-maintained in-house table of packages
  published under the repository's own `<vendor>`. Check here first before searching Packagist. An empty in-house table
  is a normal state, not a missing file.

## Does not do

- Does not reimplement what the package already covers.
- Does not add an outside dependency without checking the catalog first.
- Does not run a git operation.
