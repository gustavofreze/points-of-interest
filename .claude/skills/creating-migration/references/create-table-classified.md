# Create table with classified columns

Use this when a new table holds classified columns (PII or regulated data) and needs the table-level tag union in its
comment.

```sql
-- CREATE TABLE with classified columns. The tag-union prefix rule is owned by
-- database-migration § Data classification, not restated here. This template
-- shows the concrete shape (COMMENT = '[PII] ...'). Text columns holding
-- regulated data declare COLLATE utf8mb4_bin (or are stored encrypted/tokenized
-- per data-protection policy).

CREATE TABLE customers
(
    id         BINARY(16)   NOT NULL COMMENT '[NONE] The customer identifier in Version 7 UUID format (e.g., 01912d41-2c3d-74e5-96f7-8091a2b3c4d5).',
    name       VARCHAR(150) NOT NULL COMMENT '[PII] The customer full name (e.g., Maria Silva).',
    email      VARCHAR(255) NOT NULL COLLATE utf8mb4_bin COMMENT '[PII] The customer email address (e.g., maria.silva@example.com).',
    created_at TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) COMMENT '[NONE] The UTC date and time when the record was created in ISO 8601 format (e.g., 2026-02-13T08:49:44.931408+00:00).',
    updated_at TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6) COMMENT '[NONE] The UTC date and time when the record was last updated in ISO 8601 format (e.g., 2026-02-13T08:49:44.931408+00:00).',
    PRIMARY KEY (id),
    CONSTRAINT unq_customers_email UNIQUE (email)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_0900_ai_ci
    COMMENT = '[PII] Table used to persist customers.';
```
