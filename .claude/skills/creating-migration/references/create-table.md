# Create table

Use this when a migration creates a single table with foreign keys, explicit indexes, unique and check constraints, and
the engine block.

The SQL below is the `<database-engine>` = `mysql` binding of the neutral shape. Portable, and holding on any engine:
the column ordering, the classification tag opening every `COMMENT`, the vertical alignment of the column definitions,
the one space after each index and constraint keyword, the closing `)` alone on its line, and the naming patterns
(`database-migration` § Statement shape and § Index and constraint naming). Contextual, and changing with the engine:
the type spellings (`BINARY(16)`, `TIMESTAMP(6)`, `INT UNSIGNED`, `ENUM`) and the trailing engine block, whose portable
intents are tabulated in `database-migration` § Column types and engine.

```sql
-- Canonical CREATE TABLE shape. One CREATE TABLE per migration file.
-- Column order: id first, non-TIMESTAMP business columns grouped by prefix,
-- business TIMESTAMP(6) columns, then audit columns last. Align definitions
-- vertically. Every column carries a classification tag in its COMMENT.
-- Every foreign key column gets an explicit idx_<table>_<column> immediately
-- above its fk_<table>_<column> constraint.
-- Vertical alignment applies to the column definitions only. The index and
-- constraint entries below them take exactly one space after their keyword,
-- and the closing ) sits alone on its line with the engine block indented
-- four spaces under it (database-migration, § Statement shape).

CREATE TABLE orders
(
    id          BINARY(16)                                          NOT NULL COMMENT '[NONE] The order identifier in Version 7 UUID format (e.g., 0190d09e-a7a8-7e89-b48c-09fff0f0f0f0).',
    price       INT UNSIGNED                                        NOT NULL COMMENT '[NONE] The order price in the minor unit of the order currency (e.g., 15000).',
    status      ENUM ('PLACED', 'CONFIRMED', 'SHIPPED', 'CANCELED') NOT NULL COMMENT '[NONE] The order status (e.g., PLACED, CONFIRMED, SHIPPED, CANCELED).',
    currency    VARCHAR(3)                                          NOT NULL COMMENT '[NONE] The order currency in ISO 4217 format (e.g., BRL).',
    quantity    INT                                                 NOT NULL COMMENT '[NONE] The number of units ordered (e.g., 2).',
    product_id  BINARY(16)                                          NOT NULL COMMENT '[NONE] The product identifier in Version 7 UUID format (e.g., 01912d40-1b2c-73d4-85e6-7f8091a2b3c4).',
    customer_id BINARY(16)                                          NOT NULL COMMENT '[NONE] The customer identifier in Version 7 UUID format (e.g., 01912d41-2c3d-74e5-96f7-8091a2b3c4d5).',
    external_id VARCHAR(64)                                         NULL COLLATE utf8mb4_bin COMMENT '[NONE] The provider order identifier used for webhook correlation (e.g., acme_9f2c4d81).',
    created_at  TIMESTAMP(6)                                        NOT NULL DEFAULT CURRENT_TIMESTAMP(6) COMMENT '[NONE] The UTC date and time when the record was created in ISO 8601 format (e.g., 2026-02-13T08:49:44.931408+00:00).',
    updated_at  TIMESTAMP(6)                                        NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6) COMMENT '[NONE] The UTC date and time when the record was last updated in ISO 8601 format (e.g., 2026-02-13T08:49:44.931408+00:00).',
    PRIMARY KEY (id),
    KEY idx_orders_product_id (product_id),
    CONSTRAINT fk_orders_product_id FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE RESTRICT,
    KEY idx_orders_customer_id (customer_id),
    CONSTRAINT fk_orders_customer_id FOREIGN KEY (customer_id) REFERENCES customers (id) ON DELETE RESTRICT,
    CONSTRAINT unq_orders_external_id UNIQUE (external_id),
    CONSTRAINT chk_orders_quantity CHECK (quantity > 0)
)
    ENGINE = InnoDB
    DEFAULT CHARSET = utf8mb4
    COLLATE = utf8mb4_0900_ai_ci
    COMMENT = 'Table used to persist orders.';
```
