<?php

namespace Evolvex\InvariantSentinel\Support;

use Evolvex\InvariantSentinel\Contracts\EvidenceSanitizer;

final class RecursiveEvidenceSanitizer implements EvidenceSanitizer
{
    public function sanitize(array $data): array
    {
        $sensitive = array_map('strtolower', config('sentinel.evidence.redact', []));
        $walk = function (array $items) use (&$walk, $sensitive): array {
            foreach ($items as $key => $value) {
                if (in_array(strtolower((string) $key), $sensitive, true)) {
                    $items[$key] = '[REDACTED]';
                } elseif (is_array($value)) {
                    $items[$key] = $walk($value);
                }
            }
            return $items;
        };
        return $walk($data);
    }
}
