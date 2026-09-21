# Aggregate walkthrough (worked examples)

Read this while modeling an aggregate. It explains the two worked examples carried as templates. The invariants stay in
`php-hex-application-domain`. This file is narrative how-to, so it is NOT path-injected.

## Eventual aggregate construction

`references/aggregate-root.md` shows an eventual aggregate, the kind that records state through domain events via a
trait (`EventualAggregateRootBehavior`). The creation factory does four things:

1. Composes the child entity from the raw primitives it received (`Charge::create(...)`).
2. Generates the aggregate identity itself (`PaymentId::generate()`), never taking an id from outside.
3. Assembles the instance through the private constructor, which accepts typed properties only.
4. Emits the past-tense creation event (`PaymentCreated`) through `pushEvent`.

Reconstitution rehydrates a stored instance. An eventual aggregate uses the trait's `reconstituteStrict()`. An immutable
aggregate instead exposes a value-objects-only `from(...)` and returns a new instance on every change.

## State transitions

`references/state-transition.md` shows the guard-mutate-emit shape with `pay()` and `refund(Reason $reason)`. Each
transition guards itself by asking the status enum a predicate (`cannotBePaid()`), moves to the new status, then
constructs and emits the matching past-tense event. The eventual shape mutates `$this->status` in place. The immutable
shape returns a new instance through the private constructor. Pick one shape per aggregate and apply it consistently.
