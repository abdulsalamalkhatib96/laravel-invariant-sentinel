<?php

namespace Evolvex\InvariantSentinel\Contracts;

interface EvidenceSanitizer
{
    public function sanitize(array $data): array;
}
