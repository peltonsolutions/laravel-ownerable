<?php

namespace PeltonSolutions\LaravelOwnerable\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use PeltonSolutions\LaravelOwnerable\Contracts\CanHavePossessions;
use PeltonSolutions\LaravelOwnerable\Observers\CanBePossessedObserver;

trait CanBePossessed {

	protected static function boot(): void {
		parent::boot();
		self::observe(CanBePossessedObserver::class);
	}

	protected static function booted(): void {
		static::addGlobalScope('owner', function (Builder $query) {
			if (($ownerClassname = self::getOwnerClassname()) && ($owner = $ownerClassname::getCurrent()) && $owner instanceof CanHavePossessions) {
				$query->where('ownerable_id', $owner->getKey());
				$query->where('ownerable_type', get_class($owner));
			} else {
				$query->where('ownerable_id', null);
			}
		});
	}

	public static function getOwnerClassname():string {
		return config('ownerable.owner');
	}

	public function hasOwner(): bool {
		return (bool) $this->ownerable_id;
	}

	public function ownerable(): MorphTo {
		return $this->morphTo();
	}

}