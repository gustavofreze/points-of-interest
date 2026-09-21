# Design principles, in depth

This file is the deep reference behind the tables in `SKILL.md`. Each principle carries the rule, the smell that signals
it, a fuller good-versus-bad example in a neutral domain (Order, Customer, Account, Money, Email, Report, Shape), and
the trade-off that bounds it. SOLID is one group among the principles, not the whole story. The enforced shape for this
repository is routed in `SKILL.md` § In this service. Read the rule before you write.

## Contents

- SOLID
- Separation of Concerns
- High Cohesion
- Loose Coupling
- DRY
- KISS
- YAGNI
- Tell, Don't Ask
- Law of Demeter
- Composition over inheritance

## SOLID

The five class-level heuristics. Where each lands in ports and adapters is summarized in `SKILL.md`.

### Single Responsibility

A class should have one reason to change. Framed by the actor, it answers to one stakeholder. A report class that holds
the content rule, renders HTML, and writes a file answers to three: the analyst who owns the numbers, the designer who
owns the layout, and the operator who owns storage. A change for any one of them risks the other two.

The test is not size. A long class with one cohesive job is fine. The test is the axis of change. When two parts of a
class vary for different reasons and on different days, they belong apart.

```php
# Bad: one class, three actors, three reasons to change.
final class Report
{
    public function total(): Money { /* the analyst's rule */ }
    public function asHtml(): string { /* the designer's layout */ }
    public function saveTo(string $path): void { /* the operator's storage */ }
}

# Good: the rule keeps its home, the other concerns move to their own seams.
final class Report
{
    public function total(): Money { /* the one responsibility */ }
}

final readonly class ReportHtmlView
{
    public function render(Report $report): string { /* layout only */ }
}

interface Reports
{
    public function save(Report $report): void; // an adapter implements this
}
```

Trade-off: split too eagerly, and you scatter one cohesive idea across many tiny classes, which reads worse than the
class you feared. Split on a demonstrated second reason to change, not on a guess that one will appear.

### Open-Closed

A unit should be open to extension and closed to modification. You add behavior without editing code that already works.
With `final` classes the mechanism is composition, not subclassing, so a new variant is a new type composed in, and the
stable code does not learn about it.

The smell is a conditional over a variant that lives outside the type owning that variant and grows an arm per kind.
Inside the owner (an enum deciding over its own cases, a factory) a `match` is canonical, not a violation.

```php
# Bad: a central switch edited for every new shape.
final class AreaCalculator
{
    public function area(object $shape): float
    {
        return match (true) {
            $shape instanceof Circle => M_PI * $shape->radius ** 2,
            $shape instanceof Square => $shape->side ** 2,
            // a Triangle means editing here, again
        };
    }
}

# Good: each shape owns its area. A Triangle is a new class and edits nothing.
interface Shape
{
    public function area(): float;
}

final readonly class Circle implements Shape
{
    public function __construct(private float $radius) {}
    public function area(): float { return M_PI * $this->radius ** 2; }
}

final readonly class Square implements Shape
{
    public function __construct(private float $side) {}
    public function area(): float { return $this->side ** 2; }
}
```

Trade-off: an abstraction added before the second variant is speculative generality. Wait for the second real kind, then
close the type against the third. When the variant arms are full algorithms, escalate to a Strategy (see
`php-applying-design-patterns`).

### Liskov Substitution

If a program uses an abstraction, any implementation of that abstraction must be substitutable without breaking the
program. With `final` classes substitution exists only behind interfaces, so here the abstraction is a port and the
implementations are its adapters. The contract is the signature plus its documented obligations.

An implementation violates the principle when it throws for a declared operation, demands more than the contract states
(extra setup, a narrower input range), or returns less than promised (`null` where the contract says non-null). The
caller, holding only the abstraction, cannot defend against surprises it was told would not happen.

```php
# Bad: a holder of Account cannot trust withdraw, so the abstraction is dishonest here.
interface Account
{
    public function withdraw(Money $amount): void;
}

final class FrozenAccount implements Account
{
    public function withdraw(Money $amount): void
    {
        throw new OperationNotSupported(); // breaks every caller that relies on the contract
    }
}

# Good: separate the capability, a frozen account simply does not offer it.
interface Account
{
    public function balance(): Money;
}

interface Withdrawable extends Account
{
    public function withdraw(Money $amount): void;
}
```

The classic illustration is Square modeled as a subtype of a mutable Rectangle. Code that sets width and height
independently and expects their product as the area is correct for a Rectangle and wrong for a Square, because a Square
cannot let the two vary. The cure is the same: do not claim an is-a that the behavior cannot honor. Prefer immutable
shapes behind a `Shape` abstraction.

Trade-off: the fix is often to split the abstraction (see Interface Segregation), which multiplies interfaces. Split
only when an implementation genuinely cannot honor a method, not to chase purity.

### Interface Segregation

No client should be forced to depend on methods it does not use. A wide interface couples every implementer to the union
of all consumers' needs. The smell is an adapter that stubs or throws for a method only one other consumer cares about.
Split the interface into cohesive contracts, each centered on one resource, so a client depends only on what it actually
calls.

```php
# Bad: a read-only adapter must stub export to satisfy the interface.
interface AccountStorage
{
    public function save(Account $account): void;
    public function findById(AccountId $id): ?Account;
    public function exportCsv(): string; // only the reporting path wants this
}

# Good: two cohesive ports, each consumer depends on one.
interface Accounts
{
    public function save(Account $account): void;
    public function findById(AccountId $id): ?Account;
}

interface AccountExport
{
    public function exportCsv(): string;
}
```

Two inbound ports for the same operation with different return types are Interface Segregation applied, not duplication.
CQRS is segregation already in force: the read and write sides are never merged for reuse.

Trade-off: segment too finely, and you get a cloud of one-method interfaces that obscure the resource. Keep each port
around one resource and the operations a single consumer naturally needs together.

### Dependency Inversion

High-level modules should not depend on low-level modules. Both should depend on an abstraction, and the abstraction
belongs to the high-level side. Details depend on policy, never the reverse. In a hexagon this is exactly the outbound
port: the core declares the contract in its own domain terms, and the adapter in the outer layer implements it. The
source dependency then points inward, opposite to the flow of control.

```php
# Bad: the use case builds and names a concrete database adapter, welded to one technology.
final class TransferFunds
{
    private SqlAccounts $accounts;

    public function __construct()
    {
        $this->accounts = new SqlAccounts(); // policy reaching down to a detail
    }
}

# Good: the core depends on a port it owns, the composition root injects the concrete adapter.
interface Accounts                          // the abstraction, owned by the core
{
    public function findById(AccountId $id): ?Account;
    public function save(Account $account): void;
}

final readonly class TransferFunds
{
    public function __construct(private Accounts $accounts) {} // depends on the abstraction
}
```

This is the principle that makes the runnable-core test pass: replace every adapter with a double and the core's use
cases still run, with no UI and no database. The structural enforcement (no concrete outer-layer type crossing inward)
is owned by the architecture rule, routed in `SKILL.md`.

Trade-off: inversion costs an interface and a wiring point. Pay it where a boundary or a real second implementation
exists, not for an internal collaborator that will only ever have one form.

## Separation of Concerns

A concern is one dimension of a problem: the business rule, the presentation format, persistence, transport, validation,
logging. Separation of Concerns says each concern lives in its own section with as little overlap as the design allows,
so each can be reasoned about, changed, and tested on its own. Single Responsibility is SoC applied to a single class
(one reason to change), and the hexagon's layers are SoC applied to the whole service (the domain concern apart from the
I/O concern apart from the transport concern).

```php
# Bad: one method computes the rule, formats the output, and writes the file, three concerns interwoven.
final class MonthlyReport
{
    public function publish(string $path): void
    {
        $total = $this->sumLines();                 // business rule
        $html = '<h1>Total: ' . $total . '</h1>';   // presentation concern
        file_put_contents($path, $html);            // persistence concern
    }
}

# Good: each concern has its own home, a use case composes them.
final class MonthlyReport
{
    public function total(): Money { /* the rule only */ }
}

final readonly class ReportHtmlView
{
    public function render(MonthlyReport $report): string { /* the format only */ }
}

interface Reports
{
    public function save(MonthlyReport $report): void; // the storage only
}
```

Trade-off: separated too zealously, a single cohesive step is scattered across files the reader must reassemble before
understanding it. Separate by concern (a different reason to change), not by line.

## High Cohesion

Cohesion measures how well the parts of a module belong together. The strongest form, functional cohesion, is a module
who's every element contributes to one well-defined task. The weakest forms gather code by accident: a `Utils` bag
grouped only by being leftovers, or a `Manager` that touches everything. High cohesion makes a module easy to name, to
change for one reason, and to test in isolation.

```php
# Bad: a grab-bag, the methods share nothing but the file they sit in.
final class OrderUtils
{
    public function total(Order $order): Money { /* an order rule */ }
    public function formatEmail(Customer $customer): string { /* a presentation concern */ }
    public function connectDatabase(): Connection { /* an infrastructure concern */ }
}

# Good: each unit is cohesive around one task.
final class Order
{
    public function total(): Money { /* belongs with the order */ }
}

final readonly class CustomerEmailView
{
    public function render(Customer $customer): string { /* belongs with presentation */ }
}
```

Trade-off: cohesion and coupling trade off against each other in the small. Pushing every method onto the type that owns
its data raises cohesion, but a type that does too much for one actor has crossed back into a Single Responsibility
violation. Cohesion is one task per unit, not all tasks on one unit.

## Loose Coupling

Coupling measures how much one module must know about another. Tight coupling shows up as a change in one place forcing
changes in many (shotgun surgery), an import of a concrete type that cannot be swapped, or a caller that reaches through
an object graph (a Demeter violation). Loose coupling depends on abstractions the consumer owns, passes data rather than
letting callers navigate it, and keeps each module's public surface small.

The classic ladder ranks how two modules can bind, tightest first. Aim for the bottom rung.

| Coupling form (tightest first) | What binds the two modules                                   | The move toward looser                                                   |
|--------------------------------|--------------------------------------------------------------|--------------------------------------------------------------------------|
| Content                        | One module reaches into another's internals or private state | Tell the neighbor to act, never read its internals (LoD, Tell-Don't-Ask) |
| Control                        | One passes a flag that steers the other's branching          | Split the operation so each path is its own intention-revealing method   |
| Stamp                          | A whole record is passed when only one field is used         | Pass only the value the callee needs                                     |
| Data                           | Only the needed values pass across a narrow contract         | This is the target, an abstraction the consumer owns                     |

```php
# Bad: control coupling, a boolean steers the callee's branches from outside.
$report->render(true); // true means "as HTML", the caller drives the internal branch

# Good: each path is its own intention-revealing operation, the caller couples to data only.
$reportHtmlView->render($report);
```

Trade-off: zero coupling is impossible and undesirable, modules must collaborate to do anything. The goal is the loosest
coupling that still lets them work: data coupling across a narrow, owned contract, never content or control coupling.

## DRY

Every piece of knowledge should have a single, authoritative representation. DRY is about knowledge, not text. The same
business rule coded in two types is the defect, because a change must then be made in two places and one will be
forgotten. The fix is a single owner, with callers telling it to act.

```php
# Bad: the overdraft rule is duplicated, the two will drift.
final class Account
{
    public function canWithdraw(Money $amount): bool
    {
        return $this->balance->isAtLeast($amount); // the rule, here
    }
}

final class WithdrawalForm
{
    public function isValid(Account $account, Money $amount): bool
    {
        return $account->balance()->isAtLeast($amount); // the same rule, copied
    }
}

# Good: the rule has one owner, the form asks the owner.
final class Account
{
    public function canWithdraw(Money $amount): bool
    {
        return $this->balance->isAtLeast($amount); // the single owner of the rule
    }
}
```

Two caveats keep DRY from doing harm.

- The wrong abstraction is worse than duplication. Two blocks that look alike but encode different rules must not be
  unified. A shared helper that grows a boolean flag or a branch to serve divergent callers is the signal. Inline it
  back into each caller, then re-extract only what is truly shared.
- The rule of three. Mechanical, rule-free code (mapping plumbing, request assembly) is extracted on the third
  occurrence, within one layer and one bounded context. The first two sightings stay inline.

Trade-off: chasing DRY across a context boundary couples two models that should evolve apart. Across bounded contexts,
duplication is correct by design (one context per service).

## KISS

Keep it simple. Of the designs that satisfy the requirement, prefer the one with the fewest moving parts a reader must
hold in their head. Complexity is not free. Every abstraction, layer, generic, and configuration knob is something to
learn, test, and maintain. KISS does not mean primitive, and it is not clever-in-reverse. It means no accidental
complexity layered on top of the problem's essential complexity.

```php
# Bad: a generic, reflection-driven mechanism for a job that is a single explicit mapping.
final class DynamicMapper
{
    public function map(object $from, string $toClass): object
    {
        // reflection over properties, a config of overrides, a cache of metadata
    }
}

# Good: the explicit mapping, obvious at a glance and trivial to change.
final readonly class OrderView
{
    public function __construct(public string $id, public string $total) {}

    public static function fromOrder(Order $order): OrderView
    {
        $id = $order->id()->toString();
        $total = $order->total()->amount();

        return new OrderView(id: $id, total: $total);
    }
}
```

Trade-off: the simplest code today can become the wrong simple if the problem grows. KISS is not an excuse to skip a
boundary the architecture requires, it is a bias against complexity the problem itself does not demand. When the present
problem is genuinely complex, the simplest honest solution is still complex, and that is allowed.

## YAGNI

You are not going to need it. Do not build a generalization until a real consumer demands it. Every preventive
interface, hook, or parameter is code to read, test, and maintain for a future that may never arrive, and that often
arrives in a shape you did not predict.

```php
# Bad: a strategy seam and a config flag for a single, current behavior.
interface PricingStrategy { public function priceFor(Order $order): Money; }

final readonly class OrderPricer
{
    public function __construct(private PricingStrategy $strategy, private bool $useNewEngine) {}
}

# Good: the one behavior you actually have, extracted later if a second arrives.
final readonly class OrderPricer
{
    public function priceFor(Order $order): Money { /* the only rule today */ }
}
```

Trade-off: YAGNI is not an argument against the seams an architecture requires. A port at a real boundary is not
speculative, the dependency rule mandates it. YAGNI targets flexibility added for an imagined future, not the structure
the present design needs.

## Tell, Don't Ask

Tell an object to do something, do not pull its state out and decide on its behalf. Asking leaks the rule out of the
type that holds the data, and the rule then lives in callers that can drift. Telling keeps the decision next to the
data, where the invariant is enforced once.

```php
# Bad: the caller asks for state, then decides. The rule has left the Account.
if ($account->status() === AccountStatus::Active && $account->balance()->isAtLeast($amount)) {
    $account->setBalance($account->balance()->subtract($amount));
}

# Good: the caller tells the Account, which enforces its own invariant and answers.
$account->withdraw($amount); // throws or records the rule internally
```

Trade-off: taken to an extreme, Tell-Don't-Ask argues against every getter, which is impractical for read models and
serialization. Reads at the boundary (a query response, a presenter) legitimately ask. The principle targets decisions a
domain type should own, not every field access.

## Law of Demeter

A method should talk only to its immediate collaborators: itself, its parameters, objects it creates, and its own
fields. It should not reach through one object to operate on another's internals. The smell is a train wreck, a chain of
accessors that navigates an object graph the caller should not know.

```php
# Bad: the caller knows Customer has an Account that has a Balance with a currency.
$currency = $customer->account()->balance()->currency();

# Good: ask the nearest object for what you need, let it delegate inward.
$currency = $customer->billingCurrency();
```

Trade-off: the principle applies to behavior, not to data structures and fluent builders. A query builder or an
immutable value object chaining transformations (`Money::of(...)->add(...)`) is not a violation, because each call
returns a peer of the same abstraction rather than reaching into a stranger's internals.

## Composition over inheritance

Favor assembling behavior from injected collaborators over extending a base class to share code. Inheritance couples a
subclass to the parent's internals and is justified only by a true is-a that honors substitution (Liskov). Code sharing
alone is not such a reason. Here classes are `final` by default, so composition is the standard mechanism.

```php
# Bad: inheritance used only to reuse a method, the subclass is not truly an AuditLog.
class AuditLog
{
    protected function write(string $line): void { /* ... */ }
}

final class OrderService extends AuditLog // not an is-a, just borrowing write()
{
    public function place(Order $order): void { $this->write('placed'); }
}

# Good: inject the collaborator behind an interface.
interface AuditTrail
{
    public function record(string $event): void;
}

final readonly class OrderService
{
    public function __construct(private AuditTrail $audit) {}

    public function place(Order $order): void { $this->audit->record('placed'); }
}
```

Trade-off: composition adds a constructor parameter and a wiring point. That is the price of swappable, independently
testable behavior, and it is almost always worth paying over a fragile hierarchy.

## Sources

- Robert C. Martin, "Design Principles and Design Patterns" (2000), the paper that gathered the five.
  https://web.archive.org/web/20150906155800/http://www.objectmentor.com/resources/articles/Principles_and_Patterns.pdf
- Robert C. Martin, "Clean Code: A Handbook of Agile Software Craftsmanship" (Prentice Hall, 2008), for cohesion,
  function size, and the smells.
- Robert C. Martin, "Clean Architecture: A Craftsman's Guide to Software Structure and Design" (Prentice Hall, 2017),
  the SOLID chapters.
- Robert C. Martin, "The Single Responsibility Principle" (2014).
  https://blog.cleancoder.com/uncle-bob/2014/05/08/SingleReponsibilityPrinciple.html
- Barbara Liskov and Jeannette Wing, "A Behavioral Notion of Subtyping" (ACM TOPLAS, 1994), the origin of the
  substitution principle.
- Gamma, Helm, Johnson, and Vlissides, "Design Patterns" (1994), for "favor object composition over class inheritance".
- Andrew Hunt and David Thomas, "The Pragmatic Programmer" (1999), for DRY, Tell-Don't-Ask, and the Law of Demeter
  (Lieberherr and Holland, 1989).
- Martin Fowler, "TellDontAsk" (https://martinfowler.com/bliki/TellDontAsk.html) and "Yagni"
  (https://martinfowler.com/bliki/Yagni.html).
- Martin Fowler, "Refactoring: Improving the Design of Existing Code" (2nd ed., 2018), for the code smells (feature
  envy, shotgun surgery, divergent change) and the DRY discussion.
- Sandi Metz, "The Wrong Abstraction" (2016). https://sandimetz.com/blog/2016/1/20/the-wrong-abstraction
- Edward Yourdon and Larry Constantine, "Structured Design" (1979), the origin of the coupling and cohesion ladders.
- Meilir Page-Jones, "The Practical Guide to Structured Systems Design" (2nd ed., 1988), for the coupling and cohesion
  taxonomy in practice.
- Edsger W. Dijkstra, "On the role of scientific thought" (1974), the origin of "separation of concerns".
- Artem Zinnatullin (zakirullin), "Cognitive Load Developer's Handbook", for the working-memory model and the
  intrinsic-versus-extraneous split. https://github.com/zakirullin/cognitive-load
- John Ousterhout, "A Philosophy of Software Design" (2018), for deep versus shallow modules and defining complexity as
  what a reader must hold in their head.
