<?php

namespace PeltonSolutions\LaravelOwnerable\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphTo;

interface CanBePossessed
{
	public function ownerable(): MorphTo;

	public function hasOwner(): bool;

	public static function getOwnerClassname(): string;
}
