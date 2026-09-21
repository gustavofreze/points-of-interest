# Tactical building blocks

The objects you model a domain with. This file is the deep reference behind the table in `SKILL.md`. Each block carries
the question it answers, the rules that keep it honest, and a generic example in a neutral domain (Order, Customer,
Account, Money, Email, Document). The ENFORCED shape for this repository is routed in `SKILL.md` § In this service, read
the rule before you write.

## Contents

- Value Object
- Entity
- Aggregate and aggregate root
- Domain Event
- Repository
- Domain Service
- Factory
- Domain Exception
- Invariant
- Guarding versus validating

## Value Object

A value object answers "what is it, by its value". It has no identity. Two value objects with equal attributes are the
same thing and are interchangeable. Three properties define it.

1. Immutable. It never changes after construction. An operation returns a new instance.
2. Equal by value. Equality compares attributes, not references.
3. Self-validating. The constructor rejects an invalid state, so an instance is always valid.

A value object is the cure for primitive obsession. A raw `int` for money or a raw `string` for an email carries no
rules and no behavior. Promote each to a type that validates and behaves.

```php
final readonly class Email
{
    private function __construct(public string $value) {}

    public static function fromString(string $value): Email
    {
        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw new EmailFormatNotValid(value: $value);
        }

        return new Email(value: strtolower($value));
    }
}
```

Behavior belongs on the value object too. Arithmetic over Money, the comparison of two dates, the masking of a Document,
all live here. A primitive in a domain method signature is the signal that a value object is missing.

## Entity

An entity answers "which one is it, over time". It has identity that persists across changes to its attributes. A
customer who changes their email is the same customer. Equality is by identity, never by attributes.

An entity has a lifecycle. It is created, it transitions through states, it may be archived. It expresses those
transitions as intention-revealing methods, never as public setters. The difference between `$order->cancel($reason)`
and `$order->setStatus('canceled')` is the difference between a domain model and a data structure.

```php
final class Customer
{
    private function __construct(
        public readonly CustomerId $id,
        private Email $email,
        private CustomerStatus $status
    ) {}

    public function changeEmail(Email $email): void
    {
        if ($this->status->isClosed()) {
            throw new ClosedCustomerCannotChange();
        }

        $this->email = $email;
    }
}
```

## Aggregate and aggregate root

An aggregate answers "what changes together". It is a cluster of entities and value objects treated as one unit for the
purpose of consistency. The boundary is drawn by invariants: objects that must stay consistent on every change belong in
the same aggregate. Objects that may be eventually consistent belong in separate aggregates.

The aggregate root is the single entity that guards the boundary. Four rules follow.

1. Outside code holds a reference to the root only. Child entities are reached through it.
2. Every change to anything inside the boundary goes through a root method, so the root enforces every invariant in one
   place.
3. One aggregate references another by the other root's identifier, never by holding the foreign root as a property. The
   use case loads the counterpart separately.
4. Only values cross the boundary. A port, a service, or a closure never enters a root method as an argument and never
   lives as a property. When a behavior seems to need a collaborator, the handler resolves it first and passes the
   resulting value in. The double-dispatch variants that hand a service to an entity are rejected here by design, so the
   aggregate stays free of anything that could perform I/O (enforced as `php-hex-application-domain` § State machine
   contract).

Keep aggregates small. A large aggregate widens the transaction, increases contention, and tends to pull in objects that
have their own lifecycle. When in doubt, make a smaller aggregate and connect by id (Vernon, "Effective Aggregate
Design").

```php
final class Order
{
    private function __construct(
        public readonly OrderId $id,
        private Lines $lines,
        private OrderStatus $status
    ) {}

    public function addLine(Line $line): void
    {
        if ($this->status->isPlaced()) {
            throw new PlacedOrderCannotChange();
        }

        $this->lines = $this->lines->add(line: $line);
    }
}
```

`Line` is a child entity inside the `Order` boundary. It has no repository, is never loaded alone, and is mutated only
through `Order`. A `Customer` referenced by the order is a separate aggregate, held as a `CustomerId`, not as a
`Customer` property.

## Domain Event

A domain event answers "what happened, in the past". It records a fact the domain considers meaningful, so its name is
past tense in the business language (`OrderPlaced`, `AccountDebited`), never a technical generic (`DataUpdated`).

Three rules keep it a domain object rather than a message.

1. Immutable. A `final readonly class`.
2. It carries value objects and aggregate identifiers, not raw primitives. `OrderCanceled` exposes an `OrderId` and a
   `Reason`, not a `string`.
3. The aggregate constructs and emits the event inside its own transition method. Callers never build a domain event.
   The domain decides when an event happens.

A domain event is not an integration event. The message published to another process is translated from the domain event
at the boundary, outside the domain, so the domain event is never serialized to the wire directly.

```php
final readonly class OrderPlaced
{
    public function __construct(public OrderId $id, public CustomerId $customer) {}
}
```

## Repository

A repository answers "where do aggregates live". It presents the illusion of an in-memory collection of aggregates,
hiding persistence behind it. The domain depends on the repository as a port (an interface), and the adapter implements
it outside the domain.

One repository per aggregate root. Never one per entity. A child entity has no repository, because it is only ever
loaded and saved through its root. A repository that exposes child entities directly leaks the aggregate boundary.

The interface speaks domain types: aggregates in, aggregates or their identifiers out, never rows or primitives. The
read side is separate. A repository is for loading and saving aggregates on the write side, not for arbitrary queries
that feed a screen (that is the CQRS read model).

```php
interface Orders
{
    public function add(Order $order): void;

    public function ofId(OrderId $id): ?Order;
}
```

## Domain Service

A domain service answers "whose rule is it, when no single object owns it". It hosts a domain operation that crosses two
or more aggregates, or a calculation over value objects with no natural host. It is part of the domain and carries
business rules, not orchestration.

Domain services are rare by design. Most behavior belongs on an aggregate or a value object. A single-aggregate rule is
never a domain service, it is a method on that aggregate. Reach for a domain service only when the operation honestly
fits nowhere else.

The shape is strict. Stateless, no infrastructure property, and every parameter and return type is a domain object. A
primitive in the signature is the signal that the logic belongs on a value object instead. It never calls a repository
or an outbound port. That coordination is the handler's job, which loads through a port and then calls the service with
the loaded aggregates.

A Strategy (a domain decision with one implementation per business variant) and a Specification (a stateless business
predicate) are domain services and follow the same shape.

```php
final readonly class FundsAvailability
{
    public function withdrawableFrom(OverdraftPolicy $policy, Account $account): Money
    {
        return $policy->applyTo(balance: $account->balance());
    }
}
```

The cross-aggregate variant hosts a rule spanning two aggregates the handler has already loaded. Its parameters and
return are domain types, never primitives.

```php
final readonly class MoneyTransfer
{
    public function transfer(Money $amount, Account $source, Account $destination): TransferReceipt
    {
        $debited = $source->debit(amount: $amount);
        $credited = $destination->credit(amount: $amount);

        return TransferReceipt::issue(source: $debited, destination: $credited);
    }
}
```

## Factory

A factory answers "how is a valid one born". When constructing a valid aggregate or value object takes more than a
trivial assignment (building child entities, generating identity, enforcing a creation invariant), encapsulate it so no
caller can produce an invalid instance.

The common form is a named static factory on the type itself, named after the business act (`place`, `open`, `issue`),
falling back to `of` or `from` when no verb fits. A separate factory class is warranted only when creation spans several
types or needs collaborators.

```php
final readonly class Money
{
    public static function zero(Currency $currency): Money
    {
        return new Money(currency: $currency, amountInCents: 0);
    }
}
```

## Domain Exception

A domain exception answers "which invariant was violated". Every domain failure throws a dedicated class named after the
rule it guards, in business language (`InvalidOrderStatusTransition`, `CurrencyMismatch`, `AmountMustNotBeNegative`),
never a generic native throw with a string message.

The class is pure. No HTTP status, no formatted user message. It may carry domain context as typed constructor
parameters (value objects, identifiers, enums), never primitives and never formatted strings. Formatting to transport
happens at the boundary, outside the domain.

A not-found is not a domain exception. A lookup miss is an application failure, not a domain invariant, so it lives in
the application layer, not here. Infrastructure failures (broker, file system, database, raw transport) are not domain
exceptions either.

```php
final class CurrencyMismatch extends DomainException
{
}
```

## Invariant

An invariant answers "what must always be true". It is a business rule that holds at every observable moment, for
example an order always has at least one line item, or an account balance never goes below its overdraft limit. The
aggregate enforces every invariant inside its boundary, so an aggregate loaded from storage is always valid.

Invariants live in the model, never in a handler or a controller. A value object enforces its own invariants in the
constructor. An aggregate enforces cross-object invariants in its transition methods, guarding before it mutates and
emitting the event only after the guard passes. This is the guard-mutate-emit shape: ask the state whether the
transition is allowed, change the state, then record what happened.

The worked shape, on the aggregate root, with the factory enforcing the creation invariant and each transition guarding,
mutating, and emitting in that order, never through a setter:

```php
final class Order
{
    private function __construct(
        public readonly OrderId $id,
        private OrderStatus $status,
        private Lines $lines
    ) {}

    public static function place(CustomerId $customer, Lines $lines): Order
    {
        if ($lines->areEmpty()) {
            throw new OrderMustHaveLines();
        }

        $order = new Order(id: OrderId::generate(), status: OrderStatus::Placed, lines: $lines);
        $event = new OrderPlaced(id: $order->id, customer: $customer);
        $order->pushEvent(event: $event);

        return $order;
    }

    public function cancel(Reason $reason): void
    {
        if ($this->status->cannotBeCanceled()) {
            throw new InvalidOrderStatusTransition();
        }

        $this->status = OrderStatus::Canceled;
        $event = new OrderCanceled(id: $this->id, reason: $reason);
        $this->pushEvent(event: $event);
    }
}
```

## Guarding versus validating

Guarding and validating both reject bad data, and they are not the same move. Validating filters untrusted input at the
edge of the system. Guarding is the failsafe that keeps an always-true invariant true inside the domain. Knowing which
one you are writing tells you where the code lives and how it fails.

Validation runs on input from the outside world (an HTTP body, a query string, a broker message) before that input
reaches the domain. Bad external input is ordinary, not exceptional, so validation gathers the problems and answers the
caller with a readable error. It lives in the driver layer, as the request validation of the inbound adapter, never in
the aggregate.

Guarding runs inside the always-valid boundary, on data the domain already assumes is good. A value object constructor
rejecting an invalid state, and an aggregate transition asking the status whether the move is allowed, are guards. A
guard firing is not an ordinary event. It means a bug let a bad value cross the boundary, so a guard fails fast by
throwing a domain exception rather than returning a message.

The two layers overlap on purpose. When a request-validation rule fully covers a domain guard, that guard can never fire
through that entry point, which makes it the boundary's shielded guard (owned by the php-hex-driver-http rule). The
guard still stays in the model as the failsafe for every other caller, and the validation stays at the edge as the first
line that answers the user.
