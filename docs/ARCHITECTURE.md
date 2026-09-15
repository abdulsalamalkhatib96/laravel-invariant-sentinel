# Architecture

Laravel Invariant Sentinel is a runtime business-correctness engine. It is intentionally separated from database constraints, static analysis, request monitoring, and test-only race detection.

## Truth model

Evaluations are not boolean. Every invariant resolves to one of:

- `PASS`: business state was proven valid.
- `FAIL`: business state was proven invalid.
- `DEFERRED`: state is not valid yet but remains inside an allowed consistency/stability window.
- `UNKNOWN`: available facts are insufficient to prove either side.
- `ERROR`: Sentinel/check execution failed. This is operational failure, not a business violation.
- `NOT_APPLICABLE`: invariant does not apply to the resolved subject.

This distinction is the core defense against false positives in eventually consistent systems.

## Execution paths

### Fast path

Business events and Eloquent model events register a durable pending check after transaction commit. Pending checks coalesce by invariant + tenant + subject. Queue dispatch is only a wake-up mechanism; durable work remains in the database if the queue publish fails.

### Anti-entropy path

`sentinel:sweep` asks `SweepableInvariant` or `ViolationFindingInvariant` implementations for candidates. This path catches missed events, interrupted deployments, legacy corruption, and code paths that forgot to emit triggers.

## Concurrency

A shared cache lock serializes evaluation for a single invariant + tenant + subject. Pending checks also use generation-safe conditional completion so a trigger arriving during an in-flight evaluation cannot be accidentally deleted by the older worker.

## Persistence model

- `sentinel_states`: one current state per invariant + tenant + subject.
- `sentinel_observations`: append-only evaluation history.
- `sentinel_incidents`: violation episodes.
- `sentinel_pending_checks`: durable/coalesced work ledger.
- `sentinel_facts`: idempotent business/external facts usable by checks.
- `sentinel_audit_logs`: administrative incident actions.

## Incident model

Incidents open only on confirmed `FAIL`, never merely on `DEFERRED`, `UNKNOWN`, or `ERROR`. Flapping can be controlled with consecutive-failure and consecutive-pass thresholds. A recovered incident is resolved; a later recurrence creates a new incident episode.

## Consistency policies

`Immediate` fails immediately.

`EventuallyWithin` allows a broken intermediate state to converge before it becomes a confirmed violation.

`StableFor` requires failure to remain continuously observable for a configured duration. `UNKNOWN` or `ERROR` breaks continuity because Sentinel can no longer prove sustained failure.

## Database / replica behavior

`ModelInvariant` resolves Eloquent subjects on the model's write connection by default to reduce false failures caused by read-replica lag immediately after commit. Applications may override `resolveSubject` when a different policy is required.

## External side effects

Checks should prefer locally recorded facts over live provider calls. `EvaluationContext::externalCall()` enforces the configured external-call budget. The default budget is zero.

## Remediation

Repair is opt-in and disabled by default. A remediable invariant returns a deterministic plan first. CLI execution is explicit and followed by a recheck. Sentinel does not infer irreversible fixes from a violation.

## What Sentinel does not replace

Use database constraints, transactions, row locks, idempotency keys, and transactional outbox patterns whenever they can enforce correctness at the source. Sentinel is for correctness that spans tables, aggregates, asynchronous workflows, services, providers, or legacy state.
