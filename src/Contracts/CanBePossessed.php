<?php

namespace PeltonSolutions\LaravelOwnerable\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphTo;
use PeltonSolutions\LaravelOwnerable\Interfaces\HasName;

interface CanBePossessed
{
	
	public function ownerable(): MorphTo;

	public function hasOwner():bool;

	public static function getOwnerClassname():string;

}