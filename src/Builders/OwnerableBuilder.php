<?php

namespace PeltonSolutions\LaravelOwnerable\Builders;

use Illuminate\Database\Eloquent\Builder;

/**
 * The Eloquent builder for every model that uses CanBePossessed.
 *
 * Its forceDelete() respects the owner scope, as delete(), update() and restore() do.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends Builder<TModel>
 */
class OwnerableBuilder extends Builder
{
	/**
	 * Delete the matching rows permanently, keeping to the current owner.
	 *
	 * The 'owner' scope is applied when it is still on this query, and no other global scope is, so this still
	 * reaches soft-deleted rows as it always has. A query that removed the owner scope with
	 * withoutGlobalScope('owner') or withoutGlobalScopes() deletes across owners, as before. With no current owner
	 * the scope matches only rows that have none, as it already does for delete().
	 *
	 * @return mixed
	 */
	public function forceDelete()
	{
		if (! array_key_exists('owner', $this->scopes)) {
			return parent::forceDelete();
		}

		$query = clone $this;
		$query->withoutGlobalScopes(array_values(array_diff(array_keys($query->scopes), ['owner'])));

		return $query->toBase()->delete();
	}
}
