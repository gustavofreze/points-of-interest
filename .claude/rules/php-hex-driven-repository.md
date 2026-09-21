---
description: Write-side repository adapters under a Repository/ folder in the Driven layer. The structural residue only, constraint-violation to domain-exception translation at the write boundary. The folder layout and the Reader/Writer how-to live in php-architecture and the php-creating-driven skill.
paths:
    - "src/Driven/**/Repository/**/*.php"
---

# Repository (Driven, write side)

A database adapter persists and rehydrates an aggregate. It implements the outbound port defined in
`Application/Ports/`. This rule keeps only the constraint-translation invariant at the write boundary.

## Pre-output checklist

1. The adapter catches the database constraint and translates it to a domain exception, re-throwing when no mapping
   applies. The handler never pre-checks existence or uniqueness. See § Constraint translation.

## Structure

The folder layout (the `Repository/` subfolder, the `<Aggregate>Repository` and `Queries` classes, and the optional
`Records/` Reader and Writer) is owned by `php-architecture` and the php-creating-driven skill. The
`<Aggregate>Repository` filename pattern is owned by `php-hex-driven` (§ Adapter kinds).

## Constraint translation

Existence and uniqueness invariants are enforced by the database schema and translated by the write adapter, never
pre-checked by the handler. The translation lives wherever the INSERT or UPDATE executes: inside the
`<Aggregate>RecordWriter` when to write is extracted, or in the repository's write method when it is inline.

The write adapter catches `DatabaseFailure` and maps the violated constraint to a domain exception, re-throwing the
failure when no mapping applies:

```php
} catch (DatabaseFailure $failure) {
    throw match (true) {
        $failure->hasViolated(constraint: DatabaseConstraint::UNIQUE)   => new PaymentAlreadyExists(/* ... */),
        $failure->hasViolated(constraint: DatabaseConstraint::NOT_NULL) => new UnsupportedOrderContext(/* ... */),
        default                                                         => $failure,
    };
}
```

The mapping from driver exceptions to `DatabaseFailure` and its `DatabaseConstraint` lives only in the
`RelationalConnection` implementation for `<database-engine>`. The write adapter never imports driver exceptions.

## Read and write boundary

The shapes and how-to are in the php-creating-driven skill.
