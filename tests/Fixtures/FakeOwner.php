<?php

namespace Tests\Fixtures;

use PeltonSolutions\LaravelOwnerable\Contracts\CanBePossessed;
use PeltonSolutions\LaravelOwnerable\Contracts\CanHavePossessions;

class FakeOwner implements CanHavePossessions
{
    private static ?self $current = null;

    public static function setCurrent(?self $owner): void
    {
        static::$current = $owner;
    }

    public static function getCurrent(): ?static
    {
        return static::$current;
    }

    public function addPossession(CanBePossessed $possession): void {}

    public function getKey(): mixed
    {
        return 1;
    }
}
