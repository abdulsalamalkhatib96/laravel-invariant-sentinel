# Laravel Invariant Sentinel

**Continuous runtime verification of business invariants for Laravel 12 / 13.**

Sentinel answers a different question from static analysis and request monitoring:

> The request succeeded — but is the resulting business state actually valid?

A completed withdrawal may have a successful provider execution while its wallet debit is missing. Sentinel detects the broken **runtime business state**, records evidence, opens a deduplicated incident, retries within eventual-consistency windows, and automatically resolves the incident when the invariant becomes healthy again.

## Requirements

- PHP 8.2+
- Laravel / Illuminate 12 or 13
- A durable database for Sentinel state
- A shared lock-capable cache (Redis recommended for multi-node production)
- A real queue driver for production

## Install

```bash
composer require evolvex/laravel-invariant-sentinel
php artisan vendor:publish --tag=sentinel-config
php artisan migrate
```

Register invariants explicitly in `config/sentinel.php`:

```php
'invariants' => [
    App\\Invariants\\CompletedWithdrawalInvariant::class,
],
```

or at application boot:

```php
use Evolvex\\InvariantSentinel\\Facades\\Sentinel;

Sentinel::register(App\\Invariants\\CompletedWithdrawalInvariant::class);
```

## A production invariant

```php
final class CompletedWithdrawalInvariant extends ModelInvariant implements SweepableInvariant
{
    public static function key(): string
    {
        return 'payments.withdrawal.completed-integrity';
    }

    public function modelClass(): string
    {
        return Withdrawal::class;
    }

    public function severity(): Severity
    {
        return Severity::Critical;
    }

    public function appliesTo(mixed $withdrawal): bool
    {
        return $withdrawal->status === 'completed';
    }

    public function consistency(): ConsistencyPolicy
    {
        return ConsistencyPolicy::eventuallyWithin(seconds: 30, retryAfterSeconds: 5);
    }

    public function incidentPolicy(): IncidentPolicy
    {
        return IncidentPolicy::make(openAfterFailures: 1, resolveAfterPasses: 2);
    }

    public function checks(): array
    {
        return [
            new ProviderSucceeded,
            new ExactlyOneProviderExecution,
            new ExactlyOneWalletDebit,
        ];
    }

    public function triggers(): array
    {
        return [
            ModelTrigger::make(
                Withdrawal::class,
                ['updated'],
                fn (Withdrawal $w) => $w->wasChanged('status'),
            ),
        ];
    }

    public function candidates(): iterable
    {
        foreach (Withdrawal::query()->where('status', 'completed')->lazyById(500) as $w) {
            yield SubjectRef::fromModel($w);
        }
    }
}
```

A check returns semantic status and evidence:

```php
final class ExactlyOneWalletDebit implements InvariantCheck
{
    public function key(): string
    {
        return 'wallet-debited-exactly-once';
    }

    public function evaluate(mixed $withdrawal, EvaluationContext $context): CheckResult
    {
        $count = $withdrawal->walletTransactions()
            ->where('type', 'withdrawal')
            ->count();

        return $count === 1
            ? CheckResult::pass($this->key())
            : CheckResult::fail(
                ruleKey: $this->key(),
                code: 'wallet.debit_count_mismatch',
                expected: 1,
                actual: $count,
                evidence: [Evidence::database('wallet_transactions', 1, $count)],
            );
    }
}
```

## Status semantics

Sentinel deliberately does **not** model evaluations as boolean values.

| Status | Meaning |
|---|---|
| `pass` | Invariant is proven healthy |
| `fail` | Invariant is proven violated |
| `deferred` | Currently inconsistent but still inside the allowed consistency window |
| `unknown` | Truth cannot currently be established |
| `error` | Sentinel/check execution failed; this is not a business violation |
| `not_applicable` | The invariant does not apply to the resolved subject |

This distinction prevents transient replication lag, delayed jobs, or unavailable external facts from becoming false critical incidents.

## Durable trigger path

Triggers do not execute heavy checks in the HTTP path. They create/coalesce a record in `sentinel_pending_checks` and dispatch a drain job **after commit**. Multiple events for the same invariant + tenant + subject collapse into one pending check.

For production, run a worker for the Sentinel queue:

```bash
php artisan queue:work --queue=sentinel
```

The durable table is the source of pending work; the queue message is merely a wake-up mechanism. If queue dispatch fails, Sentinel reports the error but leaves the durable pending row intact. A periodic `sentinel:drain` is therefore recommended as recovery protection.

## Anti-entropy sweeps

Events can be lost, code paths can forget to dispatch them, and deployments can interrupt work. Critical correctness must have a second discovery path.

```bash
php artisan sentinel:sweep
php artisan sentinel:sweep payments.withdrawal.completed-integrity
```

Recommended scheduler (or set `sentinel.scheduler.enabled=true` and use `SweepTrigger` definitions):

```php
Schedule::command('sentinel:sweep')
    ->everyMinute()
    ->onOneServer()
    ->withoutOverlapping();

Schedule::command('sentinel:drain')
    ->everyMinute()
    ->onOneServer()
    ->withoutOverlapping();

Schedule::command('sentinel:prune')
    ->daily()
    ->onOneServer();
```

For very large datasets, implement `ViolationFindingInvariant` and return only subjects selected by a set-based violation query instead of hydrating every model.

## Fact store

Cross-system facts can be recorded idempotently:

```php
Sentinel::facts()->record(
    type: 'provider.withdrawal.executed',
    subject: Sentinel::subject($withdrawal),
    idempotencyKey: $providerReference,
    payload: ['provider' => 'example'],
);
```

Checks can then use `$context->facts` without making live provider requests during a sweep.

## Incident lifecycle

Sentinel stores three separate concepts:

- **State**: current health for invariant + tenant + subject.
- **Observation**: append-only evaluation history.
- **Incident**: one violation episode with open/acknowledged/resolving/resolved lifecycle.

Flapping is controlled by `IncidentPolicy` (`openAfterFailures`, `resolveAfterPasses`). Incident fingerprints exclude changing actual values, so `actual=0` becoming `actual=2` does not create a new incident for the same broken rule.

## CLI

```bash
php artisan make:invariant CompletedWithdrawalInvariant
php artisan sentinel:list
php artisan sentinel:show payments.withdrawal.completed-integrity
php artisan sentinel:check payments.withdrawal.completed-integrity 98122 --type='App\\Models\\Withdrawal'
php artisan sentinel:sweep
php artisan sentinel:drain
php artisan sentinel:incidents
php artisan sentinel:incident <id> ack --actor=ops@example.com
php artisan sentinel:incident <id> resolve --reason=manually_reconciled
php artisan sentinel:recheck <id>
php artisan sentinel:repair <id>              # dry-run
php artisan sentinel:repair <id> --execute    # requires remediation enabled
php artisan sentinel:prune
php artisan sentinel:doctor
```

## Safe remediation

Automatic repair is disabled by default. A remediable invariant implements `RemediableInvariant` and supplies a `Remediation` that first produces a deterministic `RemediationPlan`. The CLI is dry-run by default and requires both configuration enablement and explicit `--execute` plus confirmation.

Sentinel should never blindly convert `missing debit` into `Wallet::debit()`. Ambiguous remote operations must be reconciled before any irreversible repair.

## Dashboard

Enable the optional read-only dashboard:

```env
SENTINEL_DASHBOARD=true
```

Then visit `/sentinel`. The default middleware is `web`, `auth`, and `can:viewSentinel`; define the `viewSentinel` gate in the host application before enabling the dashboard.

## Multi-tenancy

Replace the default global tenant resolver:

```php
$this->app->bind(TenantResolver::class, AppTenantResolver::class);
```

Tenant identity is included in state, pending-work and evaluation lock keys to prevent cross-tenant coalescing.

## Context propagation

The default context adapter captures known Laravel Context keys when available:

- `correlation_id`
- `trace_id`
- `request_id`
- `actor_id`
- `tenant_id`

You may bind `ContextResolver` to integrate a dedicated context propagation package.

## Security and evidence

Evidence is recursively redacted according to `sentinel.evidence.redact`. Do not store secrets or raw card data as evidence. Dashboard authentication and authorization must be configured by the host application.

## Database constraints still win

Sentinel is **not** a replacement for:

- `UNIQUE`
- foreign keys
- `CHECK`
- `NOT NULL`
- transactions
- row locks
- idempotency keys

If a rule can be guaranteed transactionally by the database, enforce it there. Sentinel is for cross-table, aggregate, eventual, legacy and cross-system correctness that cannot be represented safely as a local database constraint.

## Testing

```php
Sentinel::fake();

// execute application code...

Sentinel::fake()->assertTriggered(
    'payments.withdrawal.completed-integrity',
    $withdrawal->id,
);
```

For real evaluation tests, either use the facade directly or the included `InvariantTest` harness:

```php
InvariantTest::for(CompletedWithdrawalInvariant::class)
    ->subject($withdrawal)
    ->assertPasses();
```

Facade form:

```php
$result = Sentinel::check(
    'payments.withdrawal.completed-integrity',
    $withdrawal,
);

expect($result->status)->toBe(EvaluationStatus::Pass);
```

Run package tests:

```bash
composer test
composer lint
```

## Production checklist

Run:

```bash
php artisan sentinel:doctor
```

For multi-node production use a shared lock-capable cache (typically Redis), a durable queue, periodic `sentinel:sweep`, periodic `sentinel:drain`, evidence retention/pruning, and explicit authentication around the dashboard.

## Architecture

```text
Business event / model event / manual touch
              │
              ▼
       Durable pending check
              │
              ▼
        Sentinel queue wake-up
              │
              ▼
       Distributed eval lock
              │
              ▼
       Resolve current subject
              │
              ▼
        Execute invariant checks
              │
      ┌───────┼────────┐
      ▼       ▼        ▼
    PASS    DEFERRED   FAIL / UNKNOWN / ERROR
      │       │        │
      └───────┴────────┘
              ▼
     Observation + current state
              │
              ▼
        Incident state machine
              │
         ┌────┴────┐
         ▼         ▼
       Alert    Auto-resolve

Periodic anti-entropy sweep ───────► durable pending checks
```

## License

MIT.
