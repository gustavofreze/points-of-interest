# Collation in comparisons (decision tree)

The invariant kernel stays in `php-hex-sql-queries` (§ Connection charset): the connection traffics `utf8mb4` (set once
in `src/Dependencies.php` with `'charset' => 'utf8mb4'`), `SET NAMES utf8` is prohibited, and whether an explicit clause
is load-bearing is decided by the other operand, never by reading the column declaration in isolation. This reference
carries the full decision tree.

`SET NAMES utf8` is an alias of `utf8mb3` on MySQL 8 and cannot carry 4-byte characters, so it is prohibited. A
`JSON_TABLE` string column declared without `CHARACTER SET`/`COLLATE` inherits the connection collation. Whether that
clause matters is decided by the other operand of the comparison, never by reading the column declaration in isolation.

1. **Against a real column the real column governs.** When a `JSON_TABLE` code column compares to a table column
   carrying an explicit collation (for example `payment_providers.code` at`utf8mb4_bin`), that column wins the
   comparison, the `JSON_TABLE` column yields, and no `illegal mix of collations` arises. The match follows the real
   column, case-sensitive and accent-sensitive for `_bin`. This holds for any extracted content, so the clause on the
   `JSON_TABLE` column is redundant and removing it preserves behavior. Verified on MySQL 8.4 against a column-source
   `JSON_TABLE`: `'café'` matches `'café'`, `'CAFÉ'` does not match `'café'`, and neither raises an error, identically
   with and without the clause.
2. **Against a bind parameter the `JSON_TABLE` column governs**, because the parameter is more coercible. The comparison
   then takes the connection collation, which is case-insensitive on a `_ci` connection, so the clause is load-bearing.
   Either pin it (`CHARACTER SET utf8mb4 COLLATE utf8mb4_bin`) when the stored side carries no case guarantee, or hold
   the invariant in the domain that both sides are already canonical before the query runs (for example
   `Currency::fromCode()` normalizing to ISO 4217 uppercase on every write that reaches the column). The pin lives in
   the query and the invariant lives in the domain. State which one holds, never drop the pin while assuming the other.

Fixing the connection to `utf8mb4` does not by itself make an explicit `JSON_TABLE` collation removable. The inherited
default of a `utf8mb4` connection is still case-insensitive, so the gate for removal is the operand on the other side,
not the connection. The same governance applies to any collation-less derived string column (`CAST(... AS CHAR)` and
similar), not only `JSON_TABLE`.
