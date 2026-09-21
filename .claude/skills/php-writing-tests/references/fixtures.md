# Fixtures (the single SQL boundary in tests)

Use this when an integration test needs to seed rows or read raw state. All database access in `tests/` goes through
`tests/Integration/Fixtures.php`. The invariant (Fixtures is the only class that executes raw SQL, test classes call its
public methods and never DBAL primitives directly) is owned by `php-testing-integration` (§ The Fixtures helper). This
reference carries the shape and the usage.

## The Fixtures shape

A `final readonly class` taking the DBAL `Connection`. Each public method is named after the business operation on test
data (`insertPayment`, `findChargeIdById`, `truncatePayments`), never a technical verb. Seeds run through
`executeStatement`, reads through `fetchOne`, `fetchAssociative`, or `fetchFirstColumn`, and every dynamic value flows
through a bind parameter.

```php
<?php

declare(strict_types=1);

namespace Test\Integration;

use Doctrine\DBAL\Connection;

final readonly class Fixtures
{
    public function __construct(private Connection $connection)
    {
    }

    public function insertPayment(string $id, string $status, string $organizationId): void
    {
        $this->connection->executeStatement(
            sql: '
                INSERT INTO payments (id, status, organization_id)
                VALUES (UUID_TO_BIN(:id), :status, UUID_TO_BIN(:organization_id))
            ',
            params: ['id' => $id, 'status' => $status, 'organization_id' => $organizationId]
        );
    }

    public function findChargeIdById(string $id): ?string
    {
        $chargeId = $this->connection->fetchOne(
            query: '
                SELECT pay.external_charge_id AS charge_id
                FROM payments AS pay
                WHERE pay.id = UUID_TO_BIN(:id)
            ',
            params: ['id' => $id]
        );

        return $chargeId === false ? null : (is_null($chargeId) ? null : (string)$chargeId);
    }

    public function truncatePayments(): void
    {
        $this->connection->executeStatement(sql: 'DELETE FROM payments');
    }
}
```

## The test-class usage

An integration test builds `Fixtures` over the container `Connection`, calls the public method it needs, and truncates
in `tearDown`. It never reaches for a DBAL primitive of its own.

```php
protected function tearDown(): void
{
    $fixtures = new Fixtures(connection: self::$container->get(Connection::class));

    $fixtures->truncatePaymentEvents();
    $fixtures->truncatePayments();
}
```

## The prohibited inline-SQL shape

A test class that runs SQL directly is prohibited. The moment a test calls `$this->connection->executeStatement(...)` or
any DBAL primitive, the query belongs in a named `Fixtures` method instead. A query needed by a single test still goes
into `Fixtures`, the cost of one extra method is small and the cost of raw SQL spread across `tests/` is large.

```php
// PROHIBITED. Raw SQL inside a test class, never through Fixtures.
$this->connection->executeStatement(sql: 'DELETE FROM payments');
```
