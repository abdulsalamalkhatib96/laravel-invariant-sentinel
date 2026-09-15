<?php
namespace Evolvex\InvariantSentinel\Tests\Unit;
use Evolvex\InvariantSentinel\Support\RecursiveEvidenceSanitizer; use PHPUnit\Framework\TestCase;
final class EvidenceSanitizerTest extends TestCase { public function test_redaction(): void { if (!function_exists('config')) $this->markTestSkipped('Laravel config helper required.'); config(['sentinel.evidence.redact'=>['token']]); $data=(new RecursiveEvidenceSanitizer)->sanitize(['token'=>'secret','nested'=>['token'=>'secret']]); $this->assertSame('[REDACTED]',$data['token']); $this->assertSame('[REDACTED]',$data['nested']['token']); } }
