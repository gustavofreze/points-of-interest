---
name: php-writing-documentation
description: Build the service documentation (the docs pages, openapi.yaml, and the README) as renderings of one API contract. Use when creating or updating a docs page, the OpenAPI spec, or the README.
---

# Add documentation

Builds the service documentation: the endpoint docs pages, `openapi.yaml`, and the `README.md`. These are renderings of
one API contract, so a change to an endpoint touches the docs page and `openapi.yaml` together. This skill carries the
canonical structure templates and the fixed-wording tables. The cross-file synchronization invariant is owned by the
`web-documentation` rule, and the prose style follows the global default in `CLAUDE.md` § Global defaults. The contract
facts (which endpoints exist, their paths, methods, fields, error codes, headers, pagination and idempotency semantics,
and the internal-endpoint fact) are owned by the spec, read via `php-reading-spec`, never invented here.

Target: the endpoint to document, or the README to build or restore.

Build only the artifact the request names. Start from the structure template, fill the placeholders from the spec, and
render only the sections that have content (omit empty tables and sections).

## When to use

- Document a write, read, health, or webhook endpoint (a docs page entry plus its `openapi.yaml` operation, in the same
  commit).
- Build or restore the repository `README.md`.
- Add a reusable schema, a tag, or an error-code example to `openapi.yaml`.

## When NOT to use

- Define a new endpoint's behavior, fields, or error codes: those are domain and contract decisions. Read them from the
  spec with `php-reading-spec`, then document what the spec defines.
- Build the endpoint adapter or the Request DTO: use `php-creating-driver` (write) or `php-creating-query` (read). The
  endpoint docs are produced alongside the adapter, this skill owns their shape.
- Scaffold the repository skeleton and config files: use `php-creating-skeleton`, which routes here for the README.

## Sub-flows

Pick the path by the artifact the request names. The docs page entry and the `openapi.yaml` operation always move
together, per the synchronization invariant owned by `web-documentation`.

| Intent                                                                        | Sub-flow |
|:------------------------------------------------------------------------------|:---------|
| Document an endpoint (docs page entry plus the matching `openapi.yaml` block) | endpoint |
| Build or restore the repository README                                        | readme   |

## Rules applied

Load and follow, in this priority:

- `web-documentation`: the synchronization invariant (the docs pages, `openapi.yaml`, and the README are one contract
  kept in sync).
- `php-reading-spec`: the contract facts the documentation renders (endpoints, paths, methods, fields, error codes,
  headers, pagination, idempotency, internal-endpoint fact). Read the spec, do not invent.
- The prose-punctuation global default in `CLAUDE.md` § Global defaults.

## Endpoint sub-flow

Target: the endpoint named in the request (e.g. `Payment creating`, `Find payment by id`).

### Assembly order

1. Read the spec for the endpoint contract (`php-reading-spec`): path, method, fields, status codes, headers, error
   codes, pagination, idempotency, and whether the endpoint is internal. With `<spec-root>` unset there is no spec to
   read and this step does not halt the skill. The contract is then read off the artifacts that already state it, in
   this order: `openapi.yaml`, the endpoint code and its tests, the docs page, the README. Where `openapi.yaml` does not
   exist yet, it is produced here from those same artifacts rather than treated as a missing input, and only genuinely
   undecided behavior is asked about.
2. Derive the Endpoint Name, anchor slug, and `operationId` from `references/naming-derivation.md`. The docs page
   Endpoint Name MUST equal the `openapi.yaml` `summary`.
3. Write the docs page entry from `references/docs-page.md`: header, method and URL, headers, request parameters, and
   the response blocks, rendering only the sections that have content.
4. Write the matching `openapi.yaml` operation from `references/openapi.md`: one `operationId`, one tag, exhaustive
   responses, and reusable `components/schemas/`.
5. Apply the fixed wording from `references/fixed-wording.md` for standard headers and standard responses, so the two
   renderings do not drift.
6. Verify both renderings carry the same paths, status codes, parameters, schemas, error codes, and examples, in the
   same commit.

### Completeness gate

- [ ] The endpoint appears in every source the sync invariant covers (the docs page, `openapi.yaml`, and the README
      endpoint list), never in only one, per `web-documentation`.
- [ ] The docs page Endpoint Name equals the `openapi.yaml` `summary`, and the anchor slug and `operationId` derive from
      it mechanically.
- [ ] Standard headers and standard responses use the fixed wording from `references/fixed-wording.md`.
- [ ] The `code` values match between the docs and the OpenAPI `examples`, per `web-documentation` § Synchronization.
- [ ] No invented contract fact: every path, field, error code, and status came from the spec.
- [ ] No empty section or placeholder-row table rendered.
- [ ] The prose hook is clean (no prohibited punctuation outside tables and code).

## README sub-flow

Target: the repository `README.md`.

### Assembly order

1. Start from `references/readme.md` and follow the fixed section order.
2. Fill the Overview, the use-case, query, health, and webhook link lists, each pointing to the matching docs page
   anchor.
3. Fill Installation and Environment setup from the service `make` targets and environment table.

### Completeness gate

- [ ] The fixed section order is followed, with anchor links to every section.
- [ ] The README inlines no endpoint documentation, every endpoint link points to a docs page anchor.
- [ ] The Webhooks subsection is present only when the service consumes webhooks.
- [ ] The prose hook is clean.

## Shapes and how-to (read when scaffolding)

The structure templates and the fixed-wording tables that the documentation rules used to inline live here, surfaced
only when this skill runs.

### References

- `references/docs-page.md`: the endpoint docs page structure (TOC, per-endpoint section, response blocks, and the
  paginated, rate-limited, idempotent, internal, and health variants).
- `references/openapi.md`: the `openapi.yaml` structural invariants and the operation and schema skeleton.
- `references/readme.md`: the README fixed section order and the worked example.
- `references/naming-derivation.md`: the Endpoint Name pattern per file and the mechanical Endpoint Name to anchor slug
  to `operationId` derivation.
- `references/fixed-wording.md`: the standard header wording, the standard response wording, and the domain-specific
  status-code carve-out, with the contract values pointed back to the spec.

## Does not do

- Does not invent endpoints, fields, error codes, or trust-boundary facts. Those come from the spec.
- Does not run a renderer or a docs site generator.
