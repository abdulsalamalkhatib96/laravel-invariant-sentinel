<?php
namespace Evolvex\InvariantSentinel\Tests\Feature;
use Evolvex\InvariantSentinel\Contracts\InvariantRegistry; use Evolvex\InvariantSentinel\Enums\EvaluationStatus; use Evolvex\InvariantSentinel\Evaluation\EvaluationEngine; use Evolvex\InvariantSentinel\Tests\Fixtures\TestInvariant; use Evolvex\InvariantSentinel\Tests\Fixtures\TestSubject; use Evolvex\InvariantSentinel\Tests\TestCase; use Evolvex\InvariantSentinel\ValueObjects\SubjectRef;
final class EvaluationEngineTest extends TestCase {
 public function test_it_passes_and_fails_business_state(): void { $this->app->make(InvariantRegistry::class)->register(TestInvariant::class); $subject=TestSubject::query()->create(['status'=>'active']); $engine=$this->app->make(EvaluationEngine::class); $pass=$engine->evaluate(TestInvariant::key(),SubjectRef::fromModel($subject)); $this->assertSame(EvaluationStatus::Pass,$pass->status); $subject->update(['status'=>'closed']); $fail=$engine->evaluate(TestInvariant::key(),SubjectRef::fromModel($subject->fresh())); $this->assertSame(EvaluationStatus::Fail,$fail->status); $this->assertNotNull($fail->incidentId); }
}
