# Folder skeleton

Use this shape as the canonical `src/` and `tests/` folder tree when scaffolding or restoring a service.

```text
src/
├── Application/
│   ├── Commands/        # Write intentions (write models). MAY group files under <Context>/ subfolders
│   │   └── <Context>/   # Optional per-context grouping (mirrors the bounded contexts under Domain/Models/)
│   ├── Domain/          # Core: entities, value objects, domain events, domain services, domain exceptions
│   │   ├── Models/
│   │   │   └── Commons/ # Shared primitives reused across bounded contexts
│   │   ├── Events/      # Domain event classes (optional, present only when the bounded context emits events)
│   │   ├── Exceptions/  # Domain exceptions ONLY: invariant violations + value-object self-validation
│   │   └── Services/    # Domain services (cross-aggregate business rules. Optional, present only when needed)
│   ├── Exceptions/      # Application exceptions: not-found, conflict, policy, port/gateway failures, use-case outcomes
│   ├── Handlers/        # Command executors and use-case orchestration. MAY group files under <Context>/ subfolders
│   │   └── <Context>/   # Optional per-context grouping (mirrors the bounded contexts under Domain/Models/)
│   └── Ports/           # Inbound/outbound interfaces (contracts)
├── Driven/              # Secondary adapters (outbound: database, external APIs)
│   ├── <Context>/       # Context-specific implementations
│   │   └── Repository/  # Database adapter (one Repository/ per context that persists state)
│   │       ├── <Aggregate>Repository.php  # implements the outbound port (built with reader:/writer:)
│   │       ├── Queries.php                # SQL constants for this repository
│   │       └── Records/                   # Direction-split row mappers
│   │           ├── <Aggregate>RecordReader.php  # row to aggregate (reconstitution)
│   │           └── <Aggregate>RecordWriter.php  # aggregate to row, holds the connection
│   └── Shared/          # Shared infrastructure (e.g., DBAL connection)
├── Driver/              # Primary adapters (inbound: HTTP, CLI)
│   └── Http/
│       ├── Endpoints/                  # Endpoint adapters and their request DTOs
│       │   └── <Context>/
│       │       ├── <Endpoint>.php      # implements RequestHandlerInterface
│       │       └── <Endpoint>Request.php
│       ├── Middlewares/                # PSR-15 middlewares
│       ├── Validations/                # Shared HTTP validation infrastructure
│       │   ├── RequestField.php
│       │   ├── RequestFieldTree.php
│       │   ├── RequestPayload.php
│       │   ├── RequestValidator.php
│       │   └── ValidationRules.php
│       ├── <Stack>ExceptionMapping.php  # implements the library ExceptionMapping, returns an ExceptionMappingTable
│       └── InvalidRequest.php
├── Query/               # Segregated read side (CQRS)
│   ├── <Context>/
│   │   ├── <UseCase>/
│   │   │   ├── <Finding>.php       # the read PORT (the contract, at the slice root)
│   │   │   ├── Http/               # inbound HTTP adapter
│   │   │   │   ├── <Find...>.php       # endpoint (request handler)
│   │   │   │   └── <Find...Request>.php
│   │   │   └── Database/           # outbound DB adapter and its per-slice query builder
│   │   │       ├── <Resource>FindingAdapter.php  # implements the port, runs the SQL, returns read models
│   │   │       ├── <Resource>KeysetQuery.php     # builds the seek SQL from the Keyset cursor
│   │   │       ├── <Resource>Scope.php           # the slice's scoping predicates bound into the query
│   │   │       └── Queries.php                   # optional, slice-specific SQL only (e.g. COUNT)
│   │   └── Shared/                 # Read artifacts shared by two or more slices of this context
│   │       ├── Database/
│   │       │   ├── Queries.php                # the context projection SQL (one owner)
│   │       │   └── Mapper/                    # row to read-model Data Mapper (one owner)
│   │       ├── Masking/            # context-specific output masking
│   │       └── ReadModel/          # the context read model
│   └── Shared/                     # Cross-context read-side infrastructure
│       ├── Http/                   # HTTP infrastructure for the read side
│       │   ├── <Stack>ExceptionMapping.php  # implements the library ExceptionMapping, returns an ExceptionMappingTable
│       │   ├── InvalidRequest.php
│       │   ├── RequestField.php             # shared request-validation field
│       │   ├── RequestPayload.php           # shared request-validation payload
│       │   ├── RequestValidator.php         # shared request validator
│       │   └── ValidationRules.php          # shared validation rules
│       ├── Masking/                # generic masking primitive (Mask)
│       ├── ReadModel/              # generic read-model DTOs (Reference, Money)
│       └── Exceptions/             # Cross-context read exceptions
├── <Name>Settings.php   # App-wide Settings VOs (AppSettings, DatabaseSettings)
├── Dependencies.php     # DI container registration
└── Routes.php           # HTTP route definitions
tests/
├── Integration/         # Handlers, repositories, and gateways over real infrastructure
├── Support/             # Builders, spies, fixtures, and HTTP factories (Test\Support)
└── Unit/                # Unit tests mirroring src/ (domain via the aggregate root)
    └── Driver/Http/Endpoints/   # Endpoint adapter unit tests with hand-written Spies
```
