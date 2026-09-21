---
description: Core invariants, naming, typing, formatting, and PHPDoc rules for all PHP files in services.
paths:
    - "src/**/*.php"
    - "tests/**/*.php"
---

# Code style

Conventions for every PHP file. Run `make review` after every change. **Target PHP version: the version declared in
`composer.json` `require.php`.**

## Pre-output checklist

1. `declare(strict_types=1)` is present, constructor property promotion is used, and every parameter, return type, and
   property is explicitly typed. No `else` or `else if`, no raw arrays where a typed collection or value object fits, no
   logic duplicated across two or more places. See § Core invariants.
2. Concrete classes are `final readonly` by default and never inherit from each other, and a concrete class names itself
   (never `self` or `static`) in its static-factory return type and `new` call. See § Class modifiers, § Self-reference.
3. Identifiers carry no abbreviations and no generic names, and generic technical verbs (`process`, `handle`,
   `validate`, and the like) stay out of `src/Application/`. See § Naming.
4. Identifiers, enum values, comments, and error codes use American English spelling. See § American English.
5. Casing follows PSR-12, with `snake_case` HTTP responses and database columns and `SCREAMING_SNAKE_CASE` error codes.
   See § Casing conventions.
6. Named arguments are used at own-code and tiny-blocks call sites, no argument equals the parameter's default, and the
   domain layer declares no default parameter values. See § Named arguments, § Default parameter values.
7. Parameters, promoted properties, named arguments, and fluent chains order by identifier length ascending
   (alphabetical tie-break, semantic pairs in natural order). See § Member ordering, § Parameter ordering.
8. PHPDoc lives on interfaces only, every block opens with a domain-terms summary line, and no prohibited opener or
   technical category word appears. See § PHPDoc.
9. No dead code remains, comparisons follow the `is_null`, `empty()`, and `=== ''` split with value objects compared
   through a predicate, and every `catch` carries at least one statement. See § Dead code, § Comparisons, § Exception
   handling.
10. Formatting holds: single-line signatures within 120 characters, no vertical type alignment, aligned
    `=>` columns in multi-line blocks, no trailing comma after the last multi-line element, and each method body reads
    as blank-line-separated paragraphs. See § Formatting, § Vertical rhythm.
11. `make review` was run after the change and reports clean. See the note above.

## Core invariants

Invariants with no owning section below.

1. `declare(strict_types=1)` is present.
2. All parameters, return types, and properties are explicitly typed.
3. Constructor property promotion is used.
4. No `else` or `else if`: early returns, polymorphism, or map dispatch.
5. No raw arrays where a typed collection or value object is available.
6. No O (N²) or worse, no N+1 queries. Complexity discipline is owned by the `php-applying-design-principles` skill.
7. No logic duplicated across two or more places, and no abstraction without real duplication or isolation need.
8. No justification comments (`# NOTE:`, `# REASON:`). `# TODO: <reason>` marks deferred work.
9. All class references use `use` imports, never inline fully qualified names.
10. Never create public methods, constants, or classes in `src/` solely to serve tests.
11. Use the cleanest syntax the target PHP floor natively supports over a more verbose legacy form.
12. Never pass an argument equal to the parameter's default, omit it.
13. Never wrap `new` in parentheses: PHP 8.4+ permits `new Foo()->bar()`.

## Class modifiers

Concrete classes are `final readonly` by default. `readonly` is omitted (the child stays `final`) in two unavoidable
cases: extending a non-readonly parent (`\Exception`, framework base), and an aggregate root recording domain events
through a trait with mutable event buffers. Interfaces and abstract domain bases (rare, only a genuine aggregate
hierarchy) are not `final`. Concrete classes are always `final`, inheritance between them is prohibited, use
composition.

## Self-reference

A concrete (`final`) class names itself: the static-factory return type and the `new` call use the class name, never
`self` or `static` (`of(...): Money` returning `new Money(...)`). Carve-outs: an abstract base or trait whose factory
binds to the concrete runtime class keeps `static` (late static binding), an enum keeps `self::Case` for its own cases.

## Casing conventions

Internal identifiers follow PSR-12. Beyond PSR-12: HTTP responses and database columns use **`snake_case`**, error codes
(`code` field of error payloads) use **`SCREAMING_SNAKE_CASE`**.

## Named arguments

Used at call sites for own code, tests, and third-party library methods (tiny-blocks). Never on native PHP functions,
native enum methods (`from`, `tryFrom`, `cases`), PHPUnit assertions, PSR interfaces (the contract omits parameter
names), or variadic spread (`...$args`). Native class constructors (`parent::__construct` to `\Exception` and similar)
are NOT excluded: use named arguments when a positional call would pass a value equal to a default.

## Default parameter values

The domain layer declares no default parameter values. Every parameter is required, and each input shape is a named
static factory (`Money::zero(...)`, `Money::of(...)`). Elsewhere, a default is acceptable only as a neutral identifiable
value most callers would repeat verbatim (`int $perPage = 20` matching the documented pagination default). Defaults that
skip a field or express a business choice (`bool $active = true`, `string $status = 'pending'`) are forbidden in every
layer. Methods implementing a third-party interface whose contract declares defaults are the unconditional exception.

## Private methods

Prohibited by default, the sole structural exception is the private constructor of factory patterns. The domain layer
allows a method that is pure (no I/O, no side effect, no outbound port), has two or more callers within the same class,
and represents an invariant or derived calculation of the type. Single-caller logic is inlined or expressed as a
closure, regardless of layer. Every other layer keeps the prohibition: extract a collaborator or a value object, never a
private method.

## Member ordering

Members order by visibility and group. Intra-group order is not enforced, name length is not a criterion.

At call sites, a fluent chain on the same receiver orders its methods by identifier length ascending, ignoring the
`with` prefix, with alphabetical tie-break and semantic pairs in natural order (the § Parameter ordering criterion). A
boolean toggle takes no group of its own, it orders by length like any other call. A terminal method that changes the
receiver type (`build()`) stays last whatever its length. Ordering restarts when a call returns a different type.

## Parameter ordering

Constructor, static-factory, and instance-method parameters, named arguments at call sites, property promotion, and
internal `new <ClassName>(...)` calls order by identifier length ascending, alphabetical tie-break, semantic pairs in
natural order. Declarations order within required-defaulted-variadic tiers.

## Naming

Identifiers carry no abbreviations and no generic names. A name says what in business terms, not how technically
(`$monthlyRevenue`, not `$calculatedValue`). Collections are plural.

Methods returning `bool` read as a predicate, in one of two shapes. A state predicate about the receiver itself uses an
auxiliary prefix: `is`, `has`, `can`, `cannot`, `was`, `should`. A relational predicate names the relation with a
third-person verb and takes the compared operand as an argument: `belongsTo`, `contains`, `equals`, `matches`,
`supports`. Both lists are closed.

Generic technical verbs are restricted in every form (bare, third-person, gerund): `process`, `handle`, `execute`,
`perform`, `validate`, `check`, `manage`, `do`, `run`, `apply`. As class or root identifiers they are prohibited in
every layer, and adapter classes keep their sanctioned names (`Repository`, `Gateway`, `Publisher`, `Consumer`,
`Client`, `Settings`). As method or standalone-function identifiers the scope is layered:

1. Throughout `src/Application/`, which holds the domain models under `Application/Domain/`, they stay prohibited. A
   domain verb naming the actual action always exists there, use it.
2. In the Driven, Driver, and Query adapter layers (`php-creating-driven`, `php-creating-driver`, `php-creating-query`)
   they are permitted only as a fallback when no domain verb fits the actual action. A domain verb still wins when one
   exists, the generic verb is the last resort, never a default.

Five named exceptions apply independent of layer:

1. Inbound port `handle()` methods (fixed by convention). The interface name and its PHPDoc carry the domain verb.
2. PSR-15 `process()` and `handle()` on middleware and request-handler interfaces.
3. Third-party query-builder methods (`execute`, `where`, `select`, `update`, `delete`) at call sites.
4. Project-owned canonical infrastructure abstractions whose role IS the pattern (query builder, connection, statement,
   cache) under `src/Driven/Shared/`: applies when the class is the abstraction, not when it merely uses one. Callers
   never adopt those verbs as their own public methods.
5. `create` as a public static factory on an aggregate root or entity when no business verb fits, not permitted
   elsewhere.

### Design-pattern names

A design pattern is a shape, never a name. A GoF pattern enters a change only on its own entry signal, never
preventively, and the class keeps its business name. Pattern-name suffixes (`...Strategy`, `...Decorator`, `...Factory`,
`...Visitor`, and the like) are prohibited in class names. The adapter-kind suffixes (`Repository`, `Gateway`,
`Publisher`, `Consumer`, `Client`) and `Settings` are legitimate adapter naming. Entry signals, canonical homes, and
catalogs live in the layer skills (`php-creating-application`, `php-creating-driven`, `php-creating-driver`,
`php-creating-query`).

## Dead code

No dead or unused code: a method, constant, property, parameter, import, or class with no caller or reference is
removed. A PHPDoc reference counts as a reference: an import a docblock names, either as a tag type (`@param`,
`@return`, `@throws`, `@see`) or by name in the summary prose, is live and stays.

## Comparisons

1. Null checks use `is_null($variable)`, never `$variable === null`.
2. Empty string checks on typed `string` use `$variable === ''`. Avoid `empty()` on typed strings, because `empty('0')`
   returns `true`.
3. Mixed or untyped checks (value may be `null`, empty string, `0`, or `false`) use `empty()`.
4. Two value objects of the same type compare through a predicate the type exposes
   (`$charge->amount->hasSameCurrencyAs(other: $order->amount)`), never by unwrapping state at the call site
   (`->currencyCode() !== ->currencyCode()`).

## American English

All identifiers, enum values, comments, and error codes use American English spelling: `canceled`, `organization`,
`initialize`, `behavior`, `modeling`, `labeled`, `fulfill`, `color`.

## PHPDoc

Restricted to interfaces, never on concrete classes, traits, or enums. The interface and every method on it carry
PHPDoc. Prose follows the global punctuation default.

Every block opens with a domain-terms summary line ending with a period, never naming the PHP construct, the storage
mechanism, or the design rationale. Interface summaries: nominal `"{Concept} {qualifier}."`, or
`"{Verb}ing of {object} {condition}."` when the name ends in `*ing`. Method summaries: third-person present
`"{Verbs} the {object} {effect}."`, never imperative, past, or passive.

Prohibited openers, on the interface or its methods: `Marker for`, `Contract for`, `Interface for`, `Wrapper for`,
`Implementation of`, `Represents`, `Used to`, `Helper for`, `Provides`, `Defines`, `Receives a request to`,
`Handles a request to`, `Processes`. PHPDoc never names persistence, concurrency, design justification, or technical
category words (`use case`, `marker`, `port`, `adapter`, `command`, `payload`).

The list yields in one case: an opener that is the third-person present of the domain verb the interface name already
carries. A `*ing` interface is required to share its verb with its single method (see below), so the method of a
`PasswordDefining` opens with `Defines` because that is the domain action, not because it describes the PHP construct.
The carve-out reaches only that coincidence. The same word opening a docblock that does not derive from the interface
name stays prohibited, and it never licenses renaming the interface to reach a more comfortable verb.

An interface named `*ing` shares its domain verb with its single method: gerund on the interface, third-person present
on the method. Synonyms and generic verbs are prohibited even when natural. Handlers of the same kind share one docblock
template across the codebase.

Tags carry a trailing period: `@param Type $name The {noun phrase}.`, `@return Type The {noun phrase}.`,
`@throws Exception When {domain condition}.`. Bare-tag docblocks without a summary line are prohibited.

## Collection usage

When a property or parameter is `Collectible` (from `tiny-blocks/collection`), use its fluent API (`map()`, `filter()`,
`reduce()`, `each()`, `toArray()`). Never break out to raw array functions, never materialize with `iterator_to_array`
into a raw `array_*` function.

## Time and date values

Applies only when `tiny-blocks/time` is in the `composer.json` require. When it is, use the library for every date and
time value, never native `DateTimeImmutable` or `DateTime`. Use the `Commons/` wrapper when one exists, importing the
library directly only when none does. Inside the domain this resolves to the `Commons/` wrapper.

## Common type primitives

A service keeps the value-object primitives its own domain repeats under `src/Application/Domain/Models/Commons/` (a
non-empty text, a length-capped text, a positive integer, and the like). Which ones exist, what each exposes, and which
exception each raises are per-service facts. Read them from that directory before writing a value object, never from a
list kept here. Two invariants hold whatever the catalog turns out to be:

1. Compose an existing primitive instead of re-implementing its validation inline. Where a primitive already raises the
   empty or length failure, a per-VO exception for the same condition is not introduced.
2. Normalization, charset, and format-specific rules live on the owning value object. A value object whose validation
   goes beyond what a primitive covers implements its own and does not compose that primitive.

A primitive exists only once a second value object needs it, and it exposes only the operations its consumers call
(`php-design-principles` § Precedence, no preventive abstraction). A service with a single text value object therefore
has no shared text primitive to compose and guards on the type itself, which is conformance, not a gap.

## Format strings

Assign the format string to a `$template` variable, then pass it to `sprintf` on a separate statement, keeping format
and data visually separated.

## Return statements

Guard returns (before the main logic begins) are unlimited. After the first non-guard return, at most three more
`return` statements are allowed, a hard ceiling of four non-guard returns. Beyond that, rewrite with `match` or map
dispatch to a single non-guard return.

## Function size

Length is a readability signal, not a metric: past roughly fifty logical lines a function is reviewed for the cause of
its length, never trimmed to a number. Branching or accumulated logic is rewritten with `match` or map dispatch, or the
rule moves to the owning aggregate, value object, or domain service. Mechanical low-complexity assembly is left as it
reads, or made data-driven by iterating a field-to-column table. Length is never reduced by extracting a private method
outside the domain layer (§ Private methods).

## Local variables and call nesting

In `src/`, prefer a named intermediate variable over nesting a call's result inside another call (a readability
preference, not a hard rule, applied where it helps). Typical shapes: assign the command to `$command` before
`handle(command: $command)`, resolve collaborators into named variables before a factory call, assign a constructed
value (`$event`, `$record`) before passing it by name. A fluent builder chain on a single receiver stays a chain. This
is the opposite of the test-side guidance (owned by `php-writing-tests`), where a value consumed once is inlined.

## Formatting

- **Single-line signatures within 120 characters.** Opening brace on its own line. Break to one parameter per line only
  when the single-line form overflows.
- **No vertical alignment of types.** A single space between type and variable name. Covers type-to-variable spacing
  only. SQL literals and migration column definitions have their own rules.
- **Alignment of `=>`.** Multi-line `match` expressions, array literals, and DI binding tables align the `=>` column.
  Single-line cases keep the single space.
- **No trailing comma** after the last element of any multi-line list (parameters, arguments, array literals, match
  arms).

## Vertical rhythm

A method body reads as short paragraphs, one per step, separated by a single blank line: guards one paragraph, the work
the next, the trailing `return` set off by a blank line above it. A blank line never splits a tight pair (a value built
and immediately handed to the call it serves). The unit is the step, not the line. Do not pad a one-statement body or a
guard-only method.

## Exception handling

A `catch` block always carries at least one statement: translate the caught exception into a domain or transport result,
rethrow it, or return a meaningful value expressing the recovery. An empty `catch` is prohibited. An expected
acknowledged outcome (a webhook returning the same `200` for a payment not found, an out-of-order transition, an
already-processed redelivery) is returned explicitly from the catch (`return $acknowledgement;`), never left as an empty
body.
