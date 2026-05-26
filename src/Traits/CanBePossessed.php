<?php

namespace PeltonSolutions\LaravelOwnerable\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use PeltonSolutions\LaravelOwnerable\Contracts\CanHavePossessions;
use PeltonSolutions\LaravelOwnerable\Observers\CanBePossessedObserver;

trait CanBePossessed
{
	protected static function booted(): void
	{
		self::observe(CanBePossessedObserver::class);

		static::addGlobalScope('owner', function (Builder $query) {
			$table = $query->getModel()->getTable();

			if (($ownerClassname = self::getOwnerClassname()) && ($owner = $ownerClassname::getCurrent()) && $owner instanceof CanHavePossessions) {
				$query->where("{$table}.ownerable_id", $owner->getKey());
				$query->where("{$table}.ownerable_type", get_class($owner));
			} else {
				$query->where("{$table}.ownerable_id", null);
			}
		});
	}

	public static function getOwnerClassname(): ?string
	{
		return config('ownerable.owner') ?: null;
	}

	public function hasOwner(): bool
	{
		return (bool)$this->ownerable_id;
	}

	public function ownerable(): MorphTo
	{
		return $this->morphTo();
	}
}
