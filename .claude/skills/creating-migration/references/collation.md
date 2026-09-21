# Collation decision tree

The portable intent: every text column inherits the table-level collation, and a column whose values must compare and
sort byte-for-byte overrides it with a binary collation on the column itself. That holds on any engine, and only the
collation names change.

This file is the `<database-engine>` = `mysql` binding of that intent. The table-level collation is `utf8mb4_0900_ai_ci`
(part of the charset and engine block, `database-migration` § Column types and engine, which the spec at `<spec-root>`
overrides where it speaks). The decision tree below fixes when a column storing a technical identifier, token, or hash
declares `COLLATE utf8mb4_bin` inline. The regulated-column byte-for-byte mandate (`[PII-SENSITIVE]`, `[PCI]`,
`[CONFIDENTIAL]`) is owned by database-migration § Data classification. This file walks the full decision for a given
column.

## Decide per column

1. **Binary-stored value** (`BINARY`, `VARBINARY`, such as a `BINARY(16)` UUID). It carries no collation. Declare
   nothing.
2. **Human-readable text** (names, descriptions, free-form labels). Inherit the table-level `utf8mb4_0900_ai_ci`.
   Declare no override. Accent-insensitive and case-insensitive comparison is what you want here.
3. **Technical text where case and accent must be preserved bit-for-bit** (opaque tokens, slugs, external provider keys,
   hashes, API identifiers used for correlation). Declare `COLLATE utf8mb4_bin` inline on the column.
4. **Regulated text** carrying `[PII-SENSITIVE]`, `[PCI]`, or `[CONFIDENTIAL]`. It SHOULD declare `COLLATE utf8mb4_bin`
   when stored as text, or be stored encrypted or tokenized, depending on the service's data-protection policy.

## Examples

- `name VARCHAR(150) NOT NULL COMMENT '[PII] The customer full name (e.g., Maria Silva).'` keeps the table default. It
  is human-readable text.
- `email VARCHAR(255) NOT NULL COLLATE utf8mb4_bin` preserves the address exactly.
- `external_id VARCHAR(64) NULL COLLATE utf8mb4_bin` correlates a provider key bit-for-bit.
- `id BINARY(16) NOT NULL` carries no collation. It is binary-stored.
