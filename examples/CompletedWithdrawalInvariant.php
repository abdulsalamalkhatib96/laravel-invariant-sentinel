<?php

namespace App\Invariants;

use App\Models\Withdrawal;
use Evolvex\InvariantSentinel\Consistency\ConsistencyPolicy;
use Evolvex\InvariantSentinel\Enums\Severity;
use Evolvex\InvariantSentinel\Evaluation\EvaluationContext;
use Evolvex\InvariantSentinel\ModelInvariant;
use Evolvex\InvariantSentinel\Contracts\InvariantCheck;
use Evolvex\InvariantSentinel\Contracts\SweepableInvariant;
use Evolvex\InvariantSentinel\Triggers\ModelTrigger;
use Evolvex\InvariantSentinel\ValueObjects\CheckResult;
use Evolvex\InvariantSentinel\ValueObjects\Evidence;
use Evolvex\InvariantSentinel\ValueObjects\IncidentPolicy;
use Evolvex\InvariantSentinel\ValueObjects\SubjectRef;

final class CompletedWithdrawalInvariant extends ModelInvariant implements SweepableInvariant
{
    public static function key(): string { return 'payments.withdrawal.completed-integrity'; }
    public function modelClass(): string { return Withdrawal::class; }
    public function severity(): Severity { return Severity::Critical; }
    public function consistency(): ConsistencyPolicy { return ConsistencyPolicy::eventuallyWithin(30, 5); }
    public function incidentPolicy(): IncidentPolicy { return IncidentPolicy::make(1, 2); }
    public function appliesTo(mixed $subject): bool { return $subject->status === 'completed'; }
    public function checks(): array { return [new ExactlyOneWalletDebit()]; }
    public function triggers(): array { return [ModelTrigger::make(Withdrawal::class, ['updated'], fn ($w) => $w->wasChanged('status'))]; }
    public function candidates(): iterable
    {
        foreach (Withdrawal::query()->where('status','completed')->lazyById(500) as $w) yield SubjectRef::fromModel($w);
    }
}

final class ExactlyOneWalletDebit implements InvariantCheck
{
    public function key(): string { return 'wallet-debited-exactly-once'; }
    public function evaluate(mixed $withdrawal, EvaluationContext $context): CheckResult
    {
        $count = $withdrawal->walletTransactions()->where('type','withdrawal')->count();
        return $count === 1
            ? CheckResult::pass($this->key())
            : CheckResult::fail($this->key(), 'wallet.debit_count_mismatch', 1, $count, evidence: [Evidence::database('wallet_transactions', 1, $count)]);
    }
}
