<?php
namespace Evolvex\InvariantSentinel\Tests\Unit;
use Evolvex\InvariantSentinel\Consistency\ConsistencyPolicy; use Evolvex\InvariantSentinel\Enums\EvaluationStatus; use PHPUnit\Framework\TestCase;
final class StableForPolicyTest extends TestCase { public function test_stable_for_requires_window(): void { $p=ConsistencyPolicy::stableFor(60); $now=new \DateTimeImmutable('2026-01-01 00:01:00'); $this->assertSame(EvaluationStatus::Deferred,$p->classify(EvaluationStatus::Fail,new \DateTimeImmutable('2026-01-01 00:00:30'),$now)); $this->assertSame(EvaluationStatus::Fail,$p->classify(EvaluationStatus::Fail,new \DateTimeImmutable('2025-12-31 23:59:59'),$now)); } }
