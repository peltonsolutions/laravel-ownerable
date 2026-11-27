<?php

namespace PeltonSolutions\LaravelOwnerable\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use PeltonSolutions\LaravelOwnerable\Builders\OwnerableBuilder;
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

	/**
	 * An OwnerableBuilder, so that a query-level forceDelete() respects the owner scope.
	 *
	 * A model with its own builder (a `$builder` property, or `#[UseEloquentBuilder]` on Laravel 12 and later) must
	 * have that builder extend OwnerableBuilder, and is refused with a LogicException otherwise. A model that overrides this method itself must return an
	 * OwnerableBuilder too. PHP checks that only when the override declares `: OwnerableBuilder`; an override with another
	 * return type, or none, is not checked.
	 *
	 * @param \Illuminate\Database\Query\Builder $query
	 */
	public function newEloquentBuilder($query): OwnerableBuilder
	{
		$builder = parent::newEloquentBuilder($query);

		if ($builder instanceof OwnerableBuilder) {
			return $builder;
		}

		if (Builder::class !== $builder::class) {
			throw new \LogicException(sprintf(
				'%s uses CanBePossessed, so its Eloquent builder %s must extend %s, which applies the owner scope to forceDelete().',
				static::class,
				$builder::class,
				OwnerableBuilder::class,
			));
		}

		return new OwnerableBuilder($query);
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
