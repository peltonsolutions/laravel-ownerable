<?php

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Events\Dispatcher;
use Tests\Fixtures\NoteWithOwnerableBuilder;
use Tests\Fixtures\NoteWithPlainBuilder;
use Tests\Fixtures\OwnedNote;
use Tests\Fixtures\Tenant;

/*
 * **A query-level forceDelete() respects the owner scope**, as delete(), update() and restore() do.
 *
 * Each test has two owners, A and B, with rows of the same name,
 * acts as A unless it says otherwise, and reads the table back without any scope.
 *
 * Fails when OwnerableBuilder::forceDelete() is reduced to parent::forceDelete() (the five "as A" tests, the
 * relation test, the custom-builder test and the no-owner test), when it applies every global scope rather
 * than only the owner's (the query()-only test: A's trashed row survives), when it re-adds the owner scope after
 * withoutGlobalScope('owner') (that test, the key-list test's control, and the instance test, whose own query carries no scope: B's row stays),
 * when CanBePossessed::newEloquentBuilder() is removed (the same tests as the first), and when that method stops
 * refusing a custom builder that does not extend OwnerableBuilder (the custom-builder test).
 */
beforeEach(function (): void {
	config(['ownerable.owner' => Tenant::class]);
	Tenant::setCurrent(null);

	$capsule = new Capsule(Container::getInstance());
	$capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
	$capsule->setEventDispatcher(new Dispatcher(Container::getInstance()));
	$capsule->setAsGlobal();
	$capsule->bootEloquent();
	Model::clearBootedModels();

	Capsule::schema()->create('owned_notes', function (Blueprint $table): void {
		$table->uuid('uuid')->primary();
		$table->nullableUuidMorphs('ownerable');
		$table->uuid('parent_uuid')->nullable();
		$table->string('name');
		$table->timestamps();
		$table->softDeletes();
	});

	$this->a = new Tenant('aaaaaaaa-0000-0000-0000-000000000000');
	$this->b = new Tenant('bbbbbbbb-0000-0000-0000-000000000000');
});

afterEach(function (): void {
	Tenant::setCurrent(null);
	Model::unsetEventDispatcher();
	Model::unsetConnectionResolver();
});

/** A note owned by $owner (or by nobody), written without the observer or any scope. */
function fdNote(?Tenant $owner, string $name = 'P', bool $trashed = false, ?string $parent = null): string
{
	$note = new OwnedNote();
	$note->forceFill([
		'parent_uuid' => $parent,
		'ownerable_type' => $owner ? Tenant::class : null,
		'ownerable_id' => $owner?->getKey(),
		'name' => $name,
		'deleted_at' => $trashed ? date('Y-m-d H:i:s') : null,
	])->save();

	return $note->uuid;
}

/** @return list<string> the uuids still in the table, whoever owns them and whether trashed or not */
function fdLeft(): array
{
	return Capsule::table('owned_notes')->orderBy('uuid')->pluck('uuid')->all();
}

test('premise: as A, a read sees only A\'s note, and the table holds both', function (): void {
	$a = fdNote($this->a);
	$b = fdNote($this->b);
	Tenant::setCurrent($this->a);

	expect(OwnedNote::where('name', 'P')->pluck('uuid')->all())->toBe([$a])
		->and(fdLeft())->toEqualCanonicalizing([$a, $b])
	;
});

test('as A, where()->forceDelete() respects the owner scope', function (): void {
	$a = fdNote($this->a);
	$keptA = fdNote($this->a, 'Q');
	$b = fdNote($this->b);
	Tenant::setCurrent($this->a);

	$deleted = OwnedNote::where('name', 'P')->forceDelete();

	expect($deleted)->toBe(1)
		->and(fdLeft())->not->toContain($a, 'positive control: A\'s note is deleted')
		->and(fdLeft())->toEqualCanonicalizing([$keptA, $b])
	;
});

test('as A, withTrashed()->forceDelete() respects the owner scope, trashed notes included', function (): void {
	$a = fdNote($this->a, trashed: true);
	$b = fdNote($this->b, trashed: true);
	Tenant::setCurrent($this->a);

	$deleted = OwnedNote::withTrashed()->where('name', 'P')->forceDelete();

	expect($deleted)->toBe(1)
		->and(fdLeft())->not->toContain($a, 'positive control')
		->and(fdLeft())->toBe([$b])
	;
});

test('as A, query()->forceDelete() with no condition deletes all of A\'s notes, trashed too, within the owner scope', function (): void {
	$a = fdNote($this->a);
	$trashedA = fdNote($this->a, trashed: true);
	$b = fdNote($this->b);
	$trashedB = fdNote($this->b, trashed: true);
	Tenant::setCurrent($this->a);

	$deleted = OwnedNote::query()->forceDelete();

	expect($deleted)->toBe(2, 'A\'s live and trashed notes, as forceDelete() always reached trashed rows')
		->and(fdLeft())->toEqualCanonicalizing([$b, $trashedB])
	;
});

test('as A, a builder held in a variable keeps to A too', function (): void {
	$a = fdNote($this->a);
	$b = fdNote($this->b);
	Tenant::setCurrent($this->a);

	$query = OwnedNote::query();
	$query->where('name', 'P');
	$deleted = $query->forceDelete();

	expect($deleted)->toBe(1)
		->and(fdLeft())->toBe([$b])
		->and(fdLeft())->not->toContain($a)
	;
});

test('as A, a query by a list of keys, as a bulk action builds one, respects the owner scope', function (): void {
	$a = fdNote($this->a);
	$b = fdNote($this->b);
	$kept = fdNote($this->a, 'Q');
	Tenant::setCurrent($this->a);

	// The shape of Filament's ForceDeleteBulkAction without fetching records:
	// getSelectedRecordsQuery()->forceDelete(), a query()->whereKey([...]) over the selected keys.
	$deleted = OwnedNote::query()->whereKey([$a, $b])->forceDelete();

	expect($deleted)->toBe(1)
		->and(fdLeft())->not->toContain($a, 'positive control: A\'s own key is deleted')
		->and(fdLeft())->toEqualCanonicalizing([$b, $kept])
	;

	// Control: the same key list with the owner scope removed by name reaches both owners' notes.
	$again = fdNote($this->a, 'R');
	$deleted = OwnedNote::withoutGlobalScope('owner')->whereKey([$again, $b])->forceDelete();

	expect($deleted)->toBe(2, 'the owner scope was removed on purpose')
		->and(fdLeft())->toBe([$kept])
	;
});

test('unchanged: after withoutGlobalScope(\'owner\'), forceDelete() reaches every owner\'s notes', function (): void {
	$a = fdNote($this->a);
	$b = fdNote($this->b);
	$other = fdNote($this->b, 'Q');
	Tenant::setCurrent($this->a);

	$deleted = OwnedNote::withoutGlobalScope('owner')->where('name', 'P')->forceDelete();

	expect($deleted)->toBe(2, 'B\'s note is deleted because the owner scope was removed on purpose')
		->and(fdLeft())->toBe([$other])
	;
});

test('with no current owner, forceDelete() deletes only notes that have no owner, as delete() already did', function (): void {
	$a = fdNote($this->a);
	$b = fdNote($this->b);
	$unowned = fdNote(null);

	$deleted = OwnedNote::where('name', 'P')->forceDelete();

	expect($deleted)->toBe(1)
		->and(fdLeft())->not->toContain($unowned, 'positive control: the unowned note is deleted')
		->and(fdLeft())->toEqualCanonicalizing([$a, $b])
	;

	// The same query's soft delete, for comparison: it has always kept to the scope.
	$c = fdNote(null, 'R');
	expect(OwnedNote::where('name', 'R')->delete())->toBe(1)
		->and(OwnedNote::withoutGlobalScope('owner')->withTrashed()->find($c)->trashed())->toBeTrue()
		->and(fdLeft())->toContain($a, $b)
	;
});

test('unchanged: an instance\'s forceDelete() deletes that record, whoever is current', function (): void {
	$a = fdNote($this->a);
	$b = fdNote($this->b);
	Tenant::setCurrent($this->a);

	OwnedNote::findOrFail($a)->forceDelete();
	expect(fdLeft())->toBe([$b], 'positive control: A\'s own note, loaded and deleted');

	// A record of B's loaded on purpose, as a purge or erasure does, is still deleted by its key.
	OwnedNote::withoutGlobalScope('owner')->findOrFail($b)->forceDelete();
	expect(fdLeft())->toBe([]);
});

test('as A, a relation\'s forceDelete() respects the owner scope, even for notes that point at A\'s parent', function (): void {
	$parent = fdNote($this->a, 'Parent');
	$a = fdNote($this->a, 'Reply', parent: $parent);
	$b = fdNote($this->b, 'Reply', parent: $parent);
	Tenant::setCurrent($this->a);

	$deleted = OwnedNote::findOrFail($parent)->replies()->forceDelete();

	expect($deleted)->toBe(1)
		->and(fdLeft())->not->toContain($a, 'positive control: A\'s reply is deleted')
		->and(fdLeft())->toEqualCanonicalizing([$parent, $b])
	;
});

test('a model whose own builder does not extend OwnerableBuilder is refused; one that does keeps to its owner', function (): void {
	$a = fdNote($this->a);
	$b = fdNote($this->b);
	Tenant::setCurrent($this->a);

	expect(fn () => NoteWithPlainBuilder::query())->toThrow(LogicException::class, 'must extend');

	$deleted = NoteWithOwnerableBuilder::where('name', 'P')->forceDelete();

	expect($deleted)->toBe(1, 'positive control: the extending builder deletes A\'s note')
		->and(fdLeft())->toBe([$b])
	;
});
