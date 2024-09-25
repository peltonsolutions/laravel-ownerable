<?php

namespace PeltonSolutions\LaravelOwnerable\Observers;

use PeltonSolutions\LaravelOwnerable\Contracts\CanBePossessed;
use PeltonSolutions\LaravelOwnerable\Contracts\CanHavePossessions;

class CanBePossessedObserver {
	public function creating(CanBePossessed $possession): void {
		if (!$possession->hasOwner() && ($ownerClassname = $possession::getOwnerClassname()) && ($owner = $ownerClassname::getCurrent()) && $owner instanceof CanHavePossessions) {
			$possession->ownerable()->associate($owner);
		}
	}
}
