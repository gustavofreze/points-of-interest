# GoF pattern catalog

This is the pattern catalog for the `php-applying-design-patterns` skill. It covers all 23 Gang of Four patterns
generically (intent, entry signal, structure, a minimal example, the trade-off that bounds it), then a one line note on
how each lands in this hexagonal PHP service. The examples use domain-neutral names on purpose, they teach the pattern
shape only. The this-service overlay (canonical homes, naming, escalation thresholds) is the authority for placement
here. Read the entry before writing the class. A pattern is a shape, never a class name. The class keeps its business
name (the naming invariant is owned by `php-code-style`, § Design-pattern names).

## Contents

- This-service home at a glance
- Creational
- Structural
- Behavioral
- Sources

## This-service home at a glance

| Family     | Pattern                 | In this service                                                       |
|:-----------|:------------------------|:----------------------------------------------------------------------|
| Creational | Singleton               | Provided by the DI container, constructor injection only              |
| Creational | Factory Method          | Named static factories on the type (verb, then `from`, then `create`) |
| Creational | Abstract Factory        | `Driven/`, rare, usually a second adapter behind a port instead       |
| Creational | Builder                 | Tests only, Test Data Builder under `tests/Support/Builders/`         |
| Creational | Prototype               | Provided, copy names the class (`new Money(...)`), not `clone`        |
| Structural | Adapter                 | The `Driver/` and `Driven/` layers themselves                         |
| Structural | Bridge                  | Provided by Ports and Adapters                                        |
| Structural | Composite               | Rare, a `Query/` hierarchical read response                           |
| Structural | Decorator               | `Driven/` over a port, concern prefix, adapter-kind suffix            |
| Structural | Facade                  | Provided by the tiny-blocks facades (for example `Http`)              |
| Structural | Flyweight               | Provided by enum cases                                                |
| Structural | Proxy                   | `Driven/` over a port, lazy init via native lazy objects              |
| Behavioral | Chain of Responsibility | HTTP middleware only                                                  |
| Behavioral | Command                 | `Application/Commands/`, one handler via DI                           |
| Behavioral | Interpreter             | No special stance                                                     |
| Behavioral | Iterator                | Provided by `IteratorAggregate`, generators, the domain `Collection`  |
| Behavioral | Mediator                | No special stance                                                     |
| Behavioral | Memento                 | Rare, non-event-sourced aggregate undo only                           |
| Behavioral | Observer                | Domain events                                                         |
| Behavioral | State                   | Status enum first, per-state classes past the threshold               |
| Behavioral | Strategy                | Domain service under `Domain/Services/` (Specification variant too)   |
| Behavioral | Template Method         | No special stance, prefer composition                                 |
| Behavioral | Visitor                 | A `Query/` projection or reporting adapter, never the domain          |

## Creational

### Singleton

- **Intent**: Ensure a class has exactly one instance and give a global access point to it.
- **Entry signal**: You feel tempted to reach a shared object through a static accessor from many places.
- **Structure**: A class holds its only instance in a static field and hands it out through a static accessor.
- **Example**:

```php
final class Logger
{
    private static ?Logger $instance = null;

    public static function instance(): Logger
    {
        return self::$instance ??= new Logger();
    }
}
```

- **Trade-off**: Global mutable state, hidden coupling, hard to test (you cannot substitute a double). When one instance
  is all you need, let a container own it and inject it.
- **In this service**: Already provided. The DI container owns instance lifecycle, use constructor injection only, never
  a static instance.

### Factory Method

- **Intent**: Defer to a method which concrete type to instantiate, giving the construction a name.
- **Entry signal**: Construction carries a meaningful name or branches on its input, so a raw `new` reads poorly.
- **Structure**: A creator exposes a method that returns the product, the method decides the concrete shape.
- **Example**:

```php
final readonly class Notification
{
    private function __construct(public string $channel, public string $body)
    {
    }

    public static function email(string $body): Notification
    {
        return new Notification(channel: 'email', body: $body);
    }

    public static function sms(string $body): Notification
    {
        return new Notification(channel: 'sms', body: $body);
    }
}
```

- **Trade-off**: One more indirection. When construction is a plain `new` with no name and no branch, the indirection
  earns nothing.
- **In this service**: Canonical, as named static factories on the type itself (a business verb like `open` or
  `capture`, then `from` for reconstitution, then `create`). The GoF subclass-decides form is prohibited because classes
  are final.

### Abstract Factory

- **Intent**: Produce a family of related objects together without naming their concrete classes.
- **Entry signal**: One call must return a matched set of collaborators that cannot be wired independently.
- **Structure**: A factory interface declares one creator per product, each concrete factory builds one consistent
  family.
- **Example**:

```php
interface WidgetFactory
{
    public function button(): Button;

    public function checkbox(): Checkbox;
}

final readonly class DarkWidgetFactory implements WidgetFactory
{
    public function button(): Button
    {
        return new DarkButton();
    }

    public function checkbox(): Checkbox
    {
        return new DarkCheckbox();
    }
}
```

- **Trade-off**: Many types for a small gain, and a new product means touching every factory. When the container can
  bind each member independently, a factory class only duplicates that wiring.
- **In this service**: Lives in `Driven/`, but rarely needed. Most provider variation is a second adapter behind the
  same port selected in DI, reach for the factory only when one call must return a matched set.

### Builder

- **Intent**: Assemble a complex object step by step, separating construction from the final representation.
- **Entry signal**: Many optional parts, or an object graph with sensible defaults you want to override a few at a time.
- **Structure**: A builder accumulates parts through fluent steps, a final `build()` returns the assembled product.
- **Example**:

```php
final class ReportBuilder
{
    private string $title = 'Untitled';

    /** @var list<string> */
    private array $rows = [];

    public function withTitle(string $title): ReportBuilder
    {
        $copy = clone $this;
        $copy->title = $title;

        return $copy;
    }

    public function build(): Report
    {
        return new Report(title: $this->title, rows: $this->rows);
    }
}
```

- **Trade-off**: An extra class and indirection. For an object with few required fields, named constructor arguments
  already cover the need, so a builder is overkill.
- **In this service**: Tests only, as the Test Data Builder shape under `tests/Support/Builders/`. Production
  construction uses named arguments plus the domain factories, a Builder for a domain object is prohibited.

### Prototype

- **Intent**: Create new objects by copying an existing instance rather than constructing from scratch.
- **Entry signal**: Copying a configured instance is cheaper or simpler than building one anew.
- **Structure**: A prototype exposes a copy operation that returns a clone of itself.
- **Example**:

```php
final class Document
{
    /** @param list<string> $blocks */
    public function __construct(public array $blocks)
    {
    }

    public function duplicate(): Document
    {
        return clone $this;
    }
}
```

- **Trade-off**: Deep versus shallow copy is easy to get wrong with shared references. When construction is cheap and
  explicit, copying hides intent.
- **In this service**: Already provided. Immutable value objects copy by constructing a fresh instance and naming the
  class (`new Money(...)`) in instance methods, `clone` is not the idiom.

## Structural

### Adapter

- **Intent**: Convert one interface into another so an existing class fits the contract a caller expects.
- **Entry signal**: You wrap an external API or library whose shape does not match your own port.
- **Structure**: An adapter implements your target interface and translates each call onto the adaptee.
- **Example**:

```php
interface Mailer
{
    public function send(string $to, string $body): void;
}

final readonly class VendorMailer implements Mailer
{
    public function __construct(private VendorSdkClient $client)
    {
    }

    public function send(string $to, string $body): void
    {
        $this->client->dispatch(['recipient' => $to, 'text' => $body]);
    }
}
```

- **Trade-off**: One more layer to maintain. When the external interface already matches your needs, an adapter is just
  a passthrough.
- **In this service**: Canonical. The `Driver/` (inbound) and `Driven/` (outbound) layers are themselves the Adapter,
  the adapter kinds and naming are the Driven layer's concern.

### Bridge

- **Intent**: Split an abstraction from its implementation so the two can vary independently.
- **Entry signal**: Both an abstraction and its implementation grow along separate axes and a class explosion looms.
- **Structure**: An abstraction holds a reference to an implementor interface and delegates the varying work to it.
- **Example**:

```php
interface Renderer
{
    public function drawCircle(float $radius): string;
}

abstract class Shape
{
    public function __construct(protected Renderer $renderer)
    {
    }

    abstract public function draw(): string;
}

final class Circle extends Shape
{
    public function __construct(Renderer $renderer, private float $radius)
    {
        parent::__construct($renderer);
    }

    public function draw(): string
    {
        return $this->renderer->drawCircle(radius: $this->radius);
    }
}
```

- **Trade-off**: Upfront indirection that pays off only when both axes truly vary. With a single implementation, it is
  needless structure.
- **In this service**: Satisfied. Ports and Adapters already decouple abstraction from implementation along the I/O
  axis, there is no separate Bridge construct.

### Composite

- **Intent**: Compose objects into part-whole trees and treat leaves and compositions uniformly.
- **Entry signal**: A genuine recursive hierarchy must be handled the same way at every node.
- **Structure**: A component interface is implemented by both leaves and composites, the composite holds children of the
  same type.
- **Example**:

```php
interface FileNode
{
    public function size(): int;
}

final readonly class FileLeaf implements FileNode
{
    public function __construct(private int $bytes)
    {
    }

    public function size(): int
    {
        return $this->bytes;
    }
}

final readonly class Folder implements FileNode
{
    /** @param list<FileNode> $children */
    public function __construct(private array $children)
    {
    }

    public function size(): int
    {
        return array_sum(array_map(static fn (FileNode $c): int => $c->size(), $this->children));
    }
}
```

- **Trade-off**: A uniform interface can blur leaf-only and composite-only operations. When nesting is shallow or fixed,
  flat rows plus a parent reference are simpler.
- **In this service**: Rare. The legitimate case is a hierarchical read response assembled in a `Query/` projection from
  flat rows, inside the domain it must respect the aggregate boundary.

### Decorator

- **Intent**: Attach added behavior to an object by wrapping it, keeping the same interface.
- **Entry signal**: A cross-cutting concern (cache, retry, log, metrics) must wrap a component without editing it.
- **Structure**: A decorator implements the component interface and holds an inner component it delegates to, adding
  behavior around the call.
- **Example**:

```php
interface Logger
{
    public function log(string $message): void;
}

final readonly class TimestampedLogger implements Logger
{
    public function __construct(private Logger $origin)
    {
    }

    public function log(string $message): void
    {
        $line = sprintf('[%s] %s', date('c'), $message);
        $this->origin->log(message: $line);
    }
}
```

- **Trade-off**: Many small wrappers can be hard to trace at runtime. When only one implementation will ever exist, fold
  the behavior in directly.
- **In this service**: Lives in `Driven/` over a port, concern as prefix, adapter-kind suffix preserved (for example
  `CachedThingsRepository`). Wiring is DI composition (outermost concern first), no pattern-name suffix.

### Facade

- **Intent**: Provide one simple entry point over a complex subsystem.
- **Entry signal**: Callers must orchestrate several objects or steps you would rather hide behind a single call.
- **Structure**: A facade exposes a small interface and coordinates the subsystem objects behind it.
- **Example**:

```php
final readonly class MediaConverter
{
    public function __construct(
        private AudioMixer $mixer,
        private VideoEncoder $encoder,
        private FileWriter $writer,
    ) {
    }

    public function convert(string $source, string $target): void
    {
        $audio = $this->mixer->extract(source: $source);
        $video = $this->encoder->encode(source: $source);

        $this->writer->write(audio: $audio, video: $video, target: $target);
    }
}
```

- **Trade-off**: A facade can grow into a catch-all if it accretes unrelated calls. When the subsystem is one object,
  the facade is just a passthrough.
- **In this service**: Already provided. The tiny-blocks facades (for example the `Http` facade) already wrap their
  subsystems, do not hand-build one.

### Flyweight

- **Intent**: Share immutable instances to support large numbers of fine-grained objects efficiently.
- **Entry signal**: A huge number of objects share the same intrinsic state and memory pressure is real.
- **Structure**: A factory returns shared instances keyed by intrinsic state, the extrinsic state is passed in per call.
- **Example**:

```php
enum Glyph: string
{
    case A = 'a';
    case B = 'b';

    public function render(int $x, int $y): string
    {
        return sprintf('%s at (%d,%d)', $this->value, $x, $y);
    }
}
```

- **Trade-off**: A sharing pool adds complexity and is pointless without real memory pressure. Premature pooling trades
  clarity for nothing.
- **In this service**: Satisfied. Enum cases are shared instances by definition (the intrinsic state), no manual
  instance pool.

### Proxy

- **Intent**: Provide a stand-in that controls access to another object behind the same interface.
- **Entry signal**: You need access guarding, a remote stand-in, or otherwise controlled access, with the same surface
  as the target.
- **Structure**: A proxy implements the subject interface and holds the real subject, mediating each call.
- **Example**:

```php
interface Image
{
    public function render(): string;
}

final readonly class ProtectedImage implements Image
{
    public function __construct(private AccessPolicy $policy, private Image $origin)
    {
    }

    public function render(): string
    {
        $this->policy->assertAllowed();

        return $this->origin->render();
    }
}
```

- **Trade-off**: Same shape as Decorator, so the intent is easy to confuse. When you only need deferred construction,
  native lazy objects already cover it, so no hand-written proxy.
- **In this service**: Lives in `Driven/` over a port, same rules as Decorator. Lazy initialization is handled by PHP
  native lazy objects (8.4+), do not hand-write a lazy proxy.

## Behavioral

### Chain of Responsibility

- **Intent**: Pass a request along a chain of handlers until one of them handles it.
- **Entry signal**: A pipeline where each stage may handle the request or pass it to the next.
- **Structure**: Each handler holds the next, it either acts or delegates onward.
- **Example**:

```php
interface Handler
{
    public function handle(Request $request): Response;
}

final readonly class AuthHandler implements Handler
{
    public function __construct(private Handler $next)
    {
    }

    public function handle(Request $request): Response
    {
        if (! $request->hasToken()) {
            return Response::unauthorized();
        }

        return $this->next->handle(request: $request);
    }
}
```

- **Trade-off**: A request can fall off the end unhandled, and the order is implicit. For a fixed two-step flow, a
  direct call is clearer.
- **In this service**: Canonical, as HTTP middleware only. Do not build a general in-domain handler chain.

### Command

- **Intent**: Turn a request into a standalone object carrying everything needed to perform it.
- **Entry signal**: You need to queue, log, undo, or decouple the invoker from the receiver of a request.
- **Structure**: A command object holds its parameters, a handler executes it.
- **Example**:

```php
final readonly class SendReport
{
    public function __construct(public string $recipient, public int $reportId)
    {
    }
}

final readonly class SendReportHandler
{
    public function __construct(private Mailer $mailer)
    {
    }

    public function handle(SendReport $command): void
    {
        // Perform the request described by the command.
    }
}
```

- **Trade-off**: Two types per action. For a direct synchronous call with no queueing or logging need, a plain method is
  enough.
- **In this service**: Canonical, a class under `Application/Commands/` bound to one handler via DI. There is no
  in-process command bus or event bus.

### Interpreter

- **Intent**: Define a grammar for a small language and evaluate its sentences.
- **Entry signal**: A recurring expression or grammar (a filter, a rule string) must be parsed and evaluated.
- **Structure**: Each grammar rule is a class with an `evaluate` method, composed into an expression tree.
- **Example**:

```php
interface Expression
{
    /** @param array<string, int> $context */
    public function evaluate(array $context): bool;
}

final readonly class GreaterThan implements Expression
{
    public function __construct(private string $key, private int $value)
    {
    }

    public function evaluate(array $context): bool
    {
        return ($context[$this->key] ?? 0) > $this->value;
    }
}
```

- **Trade-off**: A class per rule grows fast, and a real language outgrows it. For anything nontrivial, a parser library
  beats a hand-rolled interpreter.
- **In this service**: No special stance, apply the generic guidance.

### Iterator

- **Intent**: Traverse the elements of a collection without exposing its underlying representation.
- **Entry signal**: You need custom traversal over a collection while hiding how it is stored.
- **Structure**: An iterator exposes sequential access, the aggregate produces one on demand.
- **Example**:

```php
final class Pages implements IteratorAggregate
{
    /** @param list<string> $items */
    public function __construct(private array $items)
    {
    }

    public function getIterator(): Iterator
    {
        yield from $this->items;
    }
}
```

- **Trade-off**: Hand-rolling iteration is error-prone. When the language already iterates, a custom iterator is wasted
  code.
- **In this service**: Satisfied by the language. Use `IteratorAggregate`, generators, or the domain `Collection`, never
  hand-rolled.

### Mediator

- **Intent**: Centralize how a set of objects interact so they do not refer to each other directly.
- **Entry signal**: Many-to-many coupling between objects you want to collapse into one coordinator.
- **Structure**: Colleagues talk to a mediator, the mediator routes the interaction between them.
- **Example**:

```php
interface Mediator
{
    public function notify(string $event): void;
}

final class FormCoordinator implements Mediator
{
    public function __construct(private Button $submit)
    {
    }

    public function notify(string $event): void
    {
        match ($event) {
            'name-filled' => $this->submit->enable(),
            'name-cleared' => $this->submit->disable(),
        };
    }
}
```

- **Trade-off**: The mediator can become a god object that knows too much. With only two colleagues, a direct reference
  is simpler.
- **In this service**: No special stance, apply the generic guidance.

### Memento

- **Intent**: Capture an object's internal state so it can be restored later, without breaking encapsulation.
- **Entry signal**: An in-memory undo or snapshot of an object's state is a real requirement.
- **Structure**: The originator produces a memento of its state and later restores from one, the memento stays opaque to
  others.
- **Example**:

```php
final readonly class EditorState
{
    public function __construct(public string $content)
    {
    }
}

final class Editor
{
    private string $content = '';

    public function snapshot(): EditorState
    {
        return new EditorState(content: $this->content);
    }

    public function restore(EditorState $state): void
    {
        $this->content = $state->content;
    }
}
```

- **Trade-off**: Snapshots can be large and easy to leak state through. When the prior state can be re-read from its
  store, a memento is redundant.
- **In this service**: Rare, admitted only for a non-event-sourced aggregate that needs in-memory undo within one
  request, owned by the aggregate (`snapshot()` and `restore()`). For event-recording aggregates the event history is
  the snapshot.

### Observer

- **Intent**: Notify all dependents automatically when a subject's state changes.
- **Entry signal**: One state change must fan out to many independent reactions.
- **Structure**: A subject keeps a list of observers and notifies each on change, observers react without the subject
  knowing them concretely.
- **Example**:

```php
interface Observer
{
    public function notify(object $event): void;
}

final class Subject
{
    /** @var list<Observer> */
    private array $observers = [];

    public function subscribe(Observer $observer): void
    {
        $this->observers[] = $observer;
    }

    public function publish(object $event): void
    {
        foreach ($this->observers as $observer) {
            $observer->notify(event: $event);
        }
    }
}
```

- **Trade-off**: Notification order and cascades can be hard to follow, and a slow observer blocks the subject. For a
  single known reaction, a direct call is clearer.
- **In this service**: Canonical, as domain events. The aggregate emits, there is no manual listener wiring and no
  in-process observer registry.

### State

- **Intent**: Let an object alter its behavior when its internal state changes, as if it changed class.
- **Entry signal**: An object behaves differently across several states over several operations.
- **Structure**: A state type declares the operations, the context delegates to its current state, transitions move
  between states.
- **Example**:

```php
enum DoorState
{
    case Open;
    case Closed;
    case Locked;

    public function toggle(): self
    {
        return match ($this) {
            self::Open => self::Closed,
            self::Closed => self::Open,
            self::Locked => self::Locked,
        };
    }
}
```

- **Trade-off**: Per-state classes multiply types fast. Below a few states across a few operations, a status enum with
  predicate methods is the simpler form.
- **In this service**: A status enum with predicate and transition methods first. Per-state classes only when three or
  more states diverge across three or more operations, under `Domain/Models/<Aggregate>/`, named by the business state.

### Strategy

- **Intent**: Define a family of interchangeable algorithms behind one interface and choose one at runtime.
- **Entry signal**: A conditional chooses between full algorithm variants from a closed set.
- **Structure**: A strategy interface declares the operation, one class per algorithm, the context holds the chosen one.
- **Example**:

```php
interface Compressor
{
    public function compress(string $data): string;
}

final readonly class GzipCompressor implements Compressor
{
    public function compress(string $data): string
    {
        return gzencode($data) ?: $data;
    }
}

final readonly class NoopCompressor implements Compressor
{
    public function compress(string $data): string
    {
        return $data;
    }
}
```

- **Trade-off**: A class per variant for a one-line difference is overkill. A small `match` inside its owner reads fine
  until the arms grow into full algorithms.
- **In this service**: Lives in the domain as a domain-service interface with one implementation per variant under
  `src/Application/Domain/Services/`. The Specification variant (a business-named predicate) also lives there, generic
  combinators only on the third real composition.

### Template Method

- **Intent**: Fix the skeleton of an algorithm in a base method and defer a few steps to subtypes.
- **Entry signal**: A stable sequence repeats with only a few steps varying.
- **Structure**: A base class defines the algorithm and calls abstract hook steps that subclasses fill in.
- **Example**:

```php
abstract class ReportGenerator
{
    final public function generate(): string
    {
        return $this->header() . $this->body() . $this->footer();
    }

    abstract protected function body(): string;

    protected function header(): string
    {
        return '';
    }

    protected function footer(): string
    {
        return '';
    }
}
```

- **Trade-off**: Inheritance couples the steps to the base and resists change. When steps vary independently,
  composition (Strategy) is more flexible than subclassing.
- **In this service**: No special stance, apply the generic guidance (classes are final by default here, so prefer
  composition over the subclass hook form).

### Visitor

- **Intent**: Add a new operation over a stable object structure without changing its types.
- **Entry signal**: A new operation must traverse a stable type hierarchy and does not belong on each type.
- **Structure**: Each element accepts a visitor and dispatches to the method for its type, the visitor carries the
  operation.
- **Example**:

```php
interface ShapeVisitor
{
    public function visitCircle(Circle $circle): float;

    public function visitSquare(Square $square): float;
}

final readonly class AreaVisitor implements ShapeVisitor
{
    public function visitCircle(Circle $circle): float
    {
        return 3.14159 * $circle->radius ** 2;
    }

    public function visitSquare(Square $square): float
    {
        return $square->side ** 2;
    }
}
```

- **Trade-off**: Adding a new element type forces a change to every visitor. For a closed set, a `match` inside the
  owner is simpler than the double dispatch.
- **In this service**: Lives only at a boundary (a `Query/` read-side projection or a reporting adapter), never inside
  the domain. For a closed set, prefer a `match` inside the owner.

## Sources

Gamma, Helm, Johnson, Vlissides, "Design Patterns: Elements of Reusable Object-Oriented Software" (1994). Refactoring
Guru, "Design Patterns" (https://refactoring.guru/design-patterns).
