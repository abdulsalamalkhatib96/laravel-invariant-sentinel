<?php
namespace Evolvex\InvariantSentinel\Tests\Unit;
use Evolvex\InvariantSentinel\Consistency\ConsistencyPolicy; use Evolvex\InvariantSentinel\Enums\EvaluationStatus; use PHPUnit\Framework\TestCase;
final class ConsistencyPolicyTest extends TestCase { public function test_eventual_consistency_defers_then_fails(): void { $p=ConsistencyPolicy::eventuallyWithin(30); $now=new \DateTimeImmutable('2026-01-01 00:01:00'); $this->assertSame(EvaluationStatus::Deferred,$p->classify(EvaluationStatus::Fail,new \DateTimeImmutable('2026-01-01 00:00:45'),$now)); $this->assertSame(EvaluationStatus::Fail,$p->classify(EvaluationStatus::Fail,new \DateTimeImmutable('2026-01-01 00:00:00'),$now)); } }
