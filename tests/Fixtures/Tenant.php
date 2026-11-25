<?php

namespace Tests\Fixtures;

use PeltonSolutions\LaravelOwnerable\Contracts\CanBePossessed;
use PeltonSolutions\LaravelOwnerable\Contracts\CanHavePossessions;

/** An owner for the database tests: a key, and a "current" one, as an app's Account would have. */
class Tenant implements CanHavePossessions
{
	private static ?self $current = null;

	public function __construct(public readonly string $uuid) {}

	public static function setCurrent(?self $tenant): void
	{
		static::$current = $tenant;
	}

	public static function getCurrent(): ?static
	{
		return static::$current;
	}

	public function addPossession(CanBePossessed $possession): void {}

	public function getKey(): string
	{
		return $this->uuid;
	}
}
