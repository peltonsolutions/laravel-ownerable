<?php

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use PeltonSolutions\LaravelOwnerable\Contracts\CanBePossessed as CanBePossessedContract;
use PeltonSolutions\LaravelOwnerable\Traits\CanBePossessed;

class OwnedNote extends Model implements CanBePossessedContract
{
	use CanBePossessed;
	use HasUuids;
	use SoftDeletes;

	protected $table = 'owned_notes';
	protected $primaryKey = 'uuid';
	protected $guarded = [];

	/** Notes filed under this one. */
	public function replies(): HasMany
	{
		return $this->hasMany(self::class, 'parent_uuid');
	}
}
