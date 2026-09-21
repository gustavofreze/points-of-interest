# Ports and adapters, in depth

Deep detail behind the `php-applying-hexagonal-architecture` skill. Generic and language-agnostic, with short PHP 8
examples on a domain-neutral vocabulary (Order, Customer, Account, Money, Email, Document). The enforced specifics for
this repository live in the rules named in the skill's § In this service overlay, this file teaches the universal
concept only.

## Contents

- The dependency rule
- The runnable-core test
- Ports: inbound versus outbound
- Adapters: driver versus driven
- The composition root
- The anti-corruption boundary
- Putting it together

## The dependency rule

Source-code dependencies point inward only. Draw the system as concentric rings: the domain at the center, the
surrounding application, the adapters on the outside, the frameworks and drivers furthest out. A name resolved in an
inner ring may never refer to a name in an outer ring. The domain imports nothing from the application. The application
imports nothing from the adapters or the framework.

Control flow and source dependency point in opposite directions across an outbound boundary, and that opposition is the
whole trick. A request flows inward (a controller calls a handler), then the handler needs to reach back out to a
database, yet the source dependency of that reach still points inward, because the handler depends on a port interface
that the core owns, and the database adapter depends on that same interface from the outside.

```php
# Inner ring (core) owns the contract.
interface Accounts
{
    public function findById(AccountId $id): ?Account;
}

# Outer ring (adapter) depends on the inner contract, not the reverse.
final readonly class SqlAccounts implements Accounts
{
    public function __construct(private Connection $connection) {}

    public function findById(AccountId $id): ?Account
    {
        # SQL here, rebuild the Account aggregate, or return null.
        return null;
    }
}
```

## The runnable-core test

Cockburn's framing: you should be able to run the application with no UI and no database. Make the test operational by
trying to exercise a use case with every adapter replaced by an in-memory double.

```php
# An in-memory driven adapter, no database, satisfies the same outbound port.
final class InMemoryAccounts implements Accounts
{
    /** @var array<string, Account> */
    private array $byId = [];

    public function save(Account $account): void
    {
        $this->byId[$account->id()->toString()] = $account;
    }

    public function findById(AccountId $id): ?Account
    {
        return $this->byId[$id->toString()] ?? null;
    }
}

# The same handler runs unchanged against the in-memory adapter.
$accounts = new InMemoryAccounts();
$handler = new OpeningAccountHandler(accounts: $accounts);
```

If the use case runs here, the core is free of infrastructure. If it cannot run without booting a web server or
connecting to a real database, an outward dependency leaked inward. Find it and invert it behind a port.

## Ports: inbound versus outbound

A port is an interface the core owns. The core depends on the interface, never on whatever implements it. Ports come in
two kinds, distinguished by who drives whom.

| Kind     | Also called       | Direction of control        | Implemented by       | Example                               |
|----------|-------------------|-----------------------------|----------------------|---------------------------------------|
| Inbound  | Driving, primary  | The outside drives the core | The core (a handler) | `PlacingOrder`, `OpeningAccount`      |
| Outbound | Driven, secondary | The core drives the outside | An adapter           | `Orders`, `DocumentVerifier`, `Clock` |

An inbound port states one use case as one contract. The driver builds a command from transport input and calls the
port. The handler implements the port.

```php
interface OpeningAccount
{
    public function handle(OpenAccount $command): AccountId;
}
```

The handler implements the inbound port and depends only on outbound ports and the domain. It builds value objects from
the command, tells the aggregate to act, and calls the outbound ports it needs.

```php
final readonly class PlacingOrderHandler implements PlacingOrder
{
    public function __construct(private Orders $orders) {}

    public function handle(PlaceOrder $command): OrderId
    {
        $customer = new CustomerId(id: $command->customerId);
        $amount = Money::of(amount: $command->amount);
        $order = Order::place(amount: $amount, customer: $customer);

        $this->orders->save(order: $order);

        return $order->id();
    }
}
```

An outbound port states a capability the core needs, expressed entirely in domain terms. No SQL, no connection, no HTTP
client, no framework type appears in the signature. The adapter performs the serialization to the transport form.

```php
# Stated in domain types only. The adapter turns Email into a query parameter.
interface Customers
{
    public function findByEmail(Email $email): ?Customer;
}
```

A port that leaks a vendor type is a fake inversion. The contract below couples every caller to the database driver, so
swapping the technology forces a change in the core.

```php
# WRONG. The connection (a vendor type) leaks through the contract.
interface Customers
{
    public function findByEmail(Connection $connection, string $email): ?array;
}
```

## Adapters: driver versus driven

An adapter is a concrete class that speaks one specific technology. It sits outside the core, on one side of a port.

A driver adapter (primary) is on the inbound side. It receives input from a real transport, builds the command, and
calls the inbound port. It owns nothing about the use case itself.

```php
final readonly class OpenAccountController
{
    public function __construct(private OpeningAccount $openingAccount) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $command = OpenAccount::fromRequest($request);
        $accountId = $this->openingAccount->handle(command: $command);

        return Response::created($accountId->toString());
    }
}
```

A driven adapter (secondary) is on the outbound side. It implements an outbound port and owns the transport (SQL, an
HTTP call, a broker publish). It translates between the domain and the technology in both directions.

```php
final readonly class HttpDocumentVerifier implements DocumentVerifier
{
    public function __construct(private Client $client) {}

    public function verify(Document $document): Verification
    {
        $response = $this->client->post('/verify', ['number' => $document->toString()]);

        # Anti-corruption: translate the provider's shape into the domain's Verification.
        return Verification::fromProvider($response);
    }
}
```

The two sides follow opposite naming conventions on purpose. A driver is named for the action it exposes. A driven
adapter is named for the resource and the technology it implements. The enforced naming for this repository is owned by
the driven and driver rules in the skill overlay.

## The composition root

The composition root is the single place in the system that knows the concrete adapters. It builds the object graph and
injects each adapter into the core through its port. Nothing else constructs an adapter, and the core never calls `new`
on one.

```php
# One place wires the graph. Swap an adapter here without touching the core.
$accounts = new SqlAccounts(connection: $connection);
$documents = new HttpDocumentVerifier(client: $httpClient);

$openingAccount = new OpeningAccountHandler(accounts: $accounts, documents: $documents);

$controller = new OpenAccountController(openingAccount: $openingAccount);
```

Dependency injection is the delivery mechanism. Constructor injection keeps every dependency explicit and every
collaborator a port. Because the wiring lives in one place, a test composes the same handler with in-memory adapters,
and a different deployment composes it with real ones, with no change to the core.

## The anti-corruption boundary

Foreign concepts (another service's model, a third-party API, a legacy field name) are translated into this system's
vocabulary at the adapter boundary. The translation lives in the driven adapter, never in the handler and never in the
domain. The core stays expressed in its own ubiquitous language, protected from the shape of whatever it integrates
with.

```php
# The provider speaks "doc_status" with its own codes. The domain speaks Verification.
final readonly class Verification
{
    public static function fromProvider(array $payload): Verification
    {
        # Map the foreign field and codes into a domain value object here, at the edge.
        return new Verification(/* mapped domain state */);
    }
}
```

When the translation grows beyond a single mapping (a configurable catalog, selection rules over it), that logic belongs
inside the adapter behind the port, not in the domain. A rule that selects over an external catalog is an adapter
concern, because the domain must not learn the catalog's shape.

## Putting it together

The flow of one write use case, end to end:

1. A driver adapter receives transport input and builds a command.
2. The driver calls an inbound port. The handler behind it runs.
3. The handler builds value objects, tells the aggregate to act, and enforces invariants in the domain.
4. The handler calls outbound ports for anything it needs from the outside.
5. Driven adapters fulfill those ports, translating at the boundary.
6. The composition root decided, once, which concrete adapters back those ports.

At no step does an inner ring name an outer one. That is the property the architecture buys, and the runnable-core test
is how you confirm you still have it.
