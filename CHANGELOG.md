# Changelog

## 1.1.0

### Changed

- **A query-level `forceDelete()` on a model that uses `CanBePossessed` now respects the owner scope**, as `delete()`, `update()` and `restore()` do. `CanBePossessed` now gives its models an `OwnerableBuilder`, whose `forceDelete()` applies the owner scope, and only that scope, so it still reaches soft-deleted rows.
- **Behaviour change, hence a minor version:** with no current owner, a query-level `forceDelete()` now deletes only rows that have no owner, as `delete()` already did. Code that runs with no current owner and means to delete across owners must say so with `withoutGlobalScope('owner')` or `withoutGlobalScopes()`, which keep working as before. A relation's `forceDelete()` (for example `$post->comments()->forceDelete()`) on an owned model is constrained the same way.
- **A model with its own Eloquent builder** must have it extend `OwnerableBuilder`, or building a query for that model throws a `LogicException`. See "Models with their own builder" in the README.

### Unchanged

- A record's own `$model->forceDelete()` deletes that record.
- Queries that remove the owner scope with `withoutGlobalScope('owner')` or `withoutGlobalScopes()`.
