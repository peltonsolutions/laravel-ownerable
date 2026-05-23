<?php

use Illuminate\Database\Eloquent\Relations\MorphTo;
use PeltonSolutions\LaravelOwnerable\Observers\CanBePossessedObserver;
use Tests\Fixtures\FakeOwner;
use Tests\Fixtures\FakeOwnerable;

// Extends FakeOwnerable so we can inject a mock MorphTo for the happy-path test.
// In guard-condition tests, FakeOwnerable's ownerable() throws — proving it was never reached.
class SpyOwnerable extends FakeOwnerable
{
    public static ?MorphTo $morphTo = null;

    public function ownerable(): MorphTo
    {
        return static::$morphTo;
    }
}

beforeEach(function () {
    FakeOwner::setCurrent(null);
    SpyOwnerable::$morphTo = null;
});

test('creating does not associate when the possession already has an owner', function () {
    config(['ownerable.owner' => FakeOwner::class]);
    FakeOwner::setCurrent(new FakeOwner());

    $possession = new FakeOwnerable();
    $possession->ownerable_id = 5;

    // FakeOwnerable::ownerable() throws LogicException if called — proves it was skipped
    (new CanBePossessedObserver)->creating($possession);

    expect($possession->ownerable_id)->toBe(5);
});

test('creating does not associate when no owner class is configured', function () {
    config(['ownerable.owner' => null]);

    $possession = new FakeOwnerable();

    (new CanBePossessedObserver)->creating($possession);

    expect($possession->hasOwner())->toBeFalse();
});

test('creating does not associate when getCurrent returns null', function () {
    config(['ownerable.owner' => FakeOwner::class]);
    FakeOwner::setCurrent(null);

    $possession = new FakeOwnerable();

    (new CanBePossessedObserver)->creating($possession);

    expect($possession->hasOwner())->toBeFalse();
});

test('creating associates the current owner when all conditions are met', function () {
    $owner = new FakeOwner();
    FakeOwner::setCurrent($owner);
    config(['ownerable.owner' => FakeOwner::class]);

    $morphTo = $this->createMock(MorphTo::class);
    $morphTo->expects($this->once())->method('associate')->with($owner);

    SpyOwnerable::$morphTo = $morphTo;
    $possession = new SpyOwnerable();

    (new CanBePossessedObserver)->creating($possession);
});
