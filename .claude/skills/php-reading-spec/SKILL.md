---
name: php-reading-spec
description: Read the specification that anchors domain work, resolving both the specification root and the service slug rather than assuming either. Use when implementing business behavior, designing a data model, or defining aggregate boundaries or events.
---

# Read specification

Locates and reads the spec section that anchors domain work. Reads from the specifications at `<spec-root>`, resolving
the service from `composer.json`.

With `<spec-root>` unset, or pointing at a directory that is not there, there is no spec tier to read. Stop and ask for
the domain behavior. Do not invent it, do not substitute another path, and do not fall back to a plausible default.

Target: the domain topic to anchor, from the request.

## When to use

- Implement or change business behavior.
- Design or change a data model, aggregate boundary, or domain event.
- Resolve business-rule ambiguity: limits, lifecycles, allowed transitions.

## When NOT to use

- Style, formatting, rename, behavior-preserving refactor.
- A bugfix that does not change semantics, or adding a test for already-existing behavior.
- Code conventions: they live in `.claude/rules/` and `.claude/skills/`.

## Rules applied

No code rule. This action reads the spec, it does not apply code convention.

## Assembly order

1. Resolve the service slug: read the `name` field in `composer.json` and take the segment after the vendor slash.
2. Locate the section at `<spec-root>` whose topic matches the slug (filenames may be numbered or prefixed, so match on
   the topic, e.g. the document whose name ends in `-<service-name>.md` for `<service-name>`). With `<spec-root>` unset
   or its directory missing, stop here and ask.
3. Read and extract the rule: limits, transitions, events, invariants.
4. Return the anchored excerpt to the calling action.

## Completeness gate

- [ ] Service resolved correctly from `composer.json`.
- [ ] Right section located in the spec. With `<spec-root>` unset there is no spec to locate a section in, so this
      item is skipped rather than failed, and the skill has already stopped to ask.
- [ ] Rule extracted without inventing. If the spec does not cover it, say it does not.

## Does not do

- Does not write code.
- Does not invent a missing rule.
