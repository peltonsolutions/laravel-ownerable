<?php

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use PeltonSolutions\LaravelOwnerable\Contracts\CanBePossessed;
use PeltonSolutions\LaravelOwnerable\Traits\CanBePossessed as CanBePossessedTrait;

class ScopedFakeOwnerable implements CanBePossessed
{
    use CanBePossessedTrait {
        booted as public initScope;
    }

    public int|null $ownerable_id = null;
    public static ?\Closure $capturedScope = null;

    protected static function addGlobalScope(string $identifier, \Closure $scope): void
    {
        static::$capturedScope = $scope;
    }

    protected static function observe(mixed $classes): void {}

    public function ownerable(): MorphTo
    {
        throw new \LogicException('ownerable() should not be called');
    }
}
