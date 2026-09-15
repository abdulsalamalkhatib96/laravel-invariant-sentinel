<?php

namespace Evolvex\InvariantSentinel\Tests\Unit;

use Evolvex\InvariantSentinel\Support\RecursiveEvidenceSanitizer;
use Evolvex\InvariantSentinel\Tests\TestCase;

final class EvidenceSanitizerTest extends TestCase
{
    public function test_redaction(): void
    {
        config(['sentinel.evidence.redact' => ['token']]);

        $data = (new RecursiveEvidenceSanitizer())->sanitize([
            'token' => 'secret',
            'nested' => ['token' => 'secret'],
        ]);

        $this->assertSame('[REDACTED]', $data['token']);
        $this->assertSame('[REDACTED]', $data['nested']['token']);
    }
}
