<?php

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use PeltonSolutions\LaravelOwnerable\Contracts\CanBePossessed;
use PeltonSolutions\LaravelOwnerable\Traits\CanBePossessed as CanBePossessedTrait;

class FakeOwnerable implements CanBePossessed
{
    use CanBePossessedTrait;

    public int|null $ownerable_id = null;

    // Stub out Eloquent boot methods — not needed for unit tests
    protected static function boot(): void {}
    protected static function booted(): void {}

    public function ownerable(): MorphTo
    {
        throw new \LogicException('ownerable() should not be called in this context');
    }
}
