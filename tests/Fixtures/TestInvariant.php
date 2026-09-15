<?php
namespace Evolvex\InvariantSentinel\Tests\Fixtures;
use Evolvex\InvariantSentinel\ModelInvariant; use Evolvex\InvariantSentinel\Contracts\InvariantCheck; use Evolvex\InvariantSentinel\Evaluation\EvaluationContext; use Evolvex\InvariantSentinel\ValueObjects\CheckResult;
final class TestInvariant extends ModelInvariant { public static function key(): string{return 'test.subject.active';} public function modelClass(): string{return TestSubject::class;} public function checks(): array{return [new ActiveCheck];} }
final class ActiveCheck implements InvariantCheck { public function key(): string{return 'subject-active';} public function evaluate(mixed $subject, EvaluationContext $context): CheckResult{return $subject->status==='active'?CheckResult::pass($this->key()):CheckResult::fail($this->key(),'subject.not_active','active',$subject->status);} }
