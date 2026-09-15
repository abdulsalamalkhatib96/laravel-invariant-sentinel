<?php

namespace Evolvex\InvariantSentinel;

use Evolvex\InvariantSentinel\ValueObjects\SubjectRef;
use Illuminate\Database\Eloquent\Model;

abstract class ModelInvariant extends Invariant
{
    /** @return class-string<Model> */
    abstract public function modelClass(): string;

    public function resolveSubject(SubjectRef $subject): mixed
    {
        $class = $this->modelClass();
        return $class::onWriteConnection()->find($subject->id);
    }
}
