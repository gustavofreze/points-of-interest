# The HTTP boundary in tests how-to

Read this while testing an outbound HTTP adapter. None of it is a per-file invariant, so it is NOT path-injected. The
invariant that the boundary is faked with `InMemoryTransport` and nothing else is owned here (§ Faking the transport).
`php-testing` (§ Doubles) owns only the general boundary-double policy (Spy suffix, real objects inside the domain,
doubles only at system boundaries), and `php-hex-driven-http` (§ HTTP transport dependency) owns the production seam
(`NetworkTransport` vs `InMemoryTransport` over the `Http` facade). A gateway is exercised in `tests/Integration/` (§
Where the gateway is exercised). This file holds the faking and request-recording how-to.

## Faking the transport

The HTTP boundary is faked with `TinyBlocks\Http\Client\Transports\InMemoryTransport` and nothing else. A hand-written
class implementing `Psr\Http\Client\ClientInterface` or `TinyBlocks\Http\Client\Transport` is prohibited. Responses are
pre-built with `TinyBlocks\Http\Client\Response::with(code: ..., body: [...])` and served in FIFO order, then the `Http`
facade built over the transport is injected into the gateway adapter under test, exactly as `Http` over
`NetworkTransport` is injected in production (the production contract is owned by `php-hex-driven-http`).

The transport setup and injection shape lives in `references/http-boundary-in-tests.md`.

## Where the gateway is exercised

An outbound HTTP gateway is exercised in `tests/Integration/`, never in `tests/Unit/` (it is a `src/Driven/` adapter,
see the unit-test decision tree). Assertions target the domain object the adapter returns, the translation of an error
status into a domain exception, and the outbound request itself.

## Recording requests

`InMemoryTransport` records every request it receives. Read them with `receivedRequests()` or the latest with
`lastReceivedRequest()`, and assert on the recorded `Request` (`method()`, `url()`, `body()`, `headers()`) to cover the
request construction the gateway performs. The recorded request is the one `Http` resolved, with the absolute URL and
the default headers merged, so assert against that resolved form. When the call's effect across multiple invocations
matters, queue the follow-up `Response`, and rely on `TinyBlocks\Http\Exceptions\NoMoreResponses` to surface an
unexpected extra call against an exhausted queue.
