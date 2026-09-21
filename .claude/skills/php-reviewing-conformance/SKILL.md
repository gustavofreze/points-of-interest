---
name: php-reviewing-conformance
description: Read-only audit of code against the rules, CLAUDE.md, and the spec, producing a Markdown findings report. Use when asked to audit code, review conformance, or check rule adherence.
---

# Review conformance

Read-only audit against the rules, the `CLAUDE.md`, and the spec. Produces findings in Markdown, never edits.

Target: the path to audit, or the whole service when none is named.

## When to use

- Audit code, review conformance, check adherence to the rules, produce a findings report.
- Confirm a change holds against `CLAUDE.md`, the layer rules, and the spec before it ships.

## When NOT to use

- Fix what it finds: this action only reports. The fix is the layer action responsibility (`php-creating-driver`,
  `php-creating-driven`, `php-creating-query`, `php-creating-application`, or `php-implementing-features`).
- Decide whether a design pattern belongs: that is a selection call owned by `php-applying-design-patterns`, not a
  conformance audit.
- Stand in for reading the spec on domain work: anchor on `php-reading-spec` for the business behavior. This skill
  consults the spec but does not replace that read.

## Rules applied

- All rules under `.claude/rules/`, each file checked against the rule of its layer.
- `CLAUDE.md`, both halves: the project's declarations and the reading table above the dividing line, and the portable
  rules below it (token resolution, capability absence, commands, dependency policy, global defaults, authority). A rule
  stated in `CLAUDE.md` is audited exactly as one stated in a rule file.
- The spec, via `php-reading-spec`, when business rule is in play.

## Assembly order

1. Define the scope of paths to audit.
2. Anchor on the spec (`php-reading-spec`) if there is business behavior.
3. Check each file against the rule of its layer, and the `CLAUDE.md`.
4. Write the findings in structured Markdown: file, line, violated rule, evidence.

## Completeness gate

- [ ] Read-only: no code edit.
- [ ] Each finding cites the rule and the location (file and line).
- [ ] Conformance findings separated from style preference.
- [ ] Report in structured Markdown.

## Additional resources

- **`assets/findings-report-template.md`**: Template for the structured Markdown findings report. Copy and fill in:
  scope, date, and one entry per finding with file, line, rule, evidence, and finding.

## Does not do

- Does not invent a violation: no evidence, no finding.
