<?php

use Illuminate\Database\Eloquent\Builder;
use Tests\Fixtures\FakeOwner;
use Tests\Fixtures\ScopedFakeOwnerable;

beforeEach(function () {
    FakeOwner::setCurrent(null);
    ScopedFakeOwnerable::$capturedScope = null;
    ScopedFakeOwnerable::initScope();
});

test('global scope filters by ownerable_id and ownerable_type when owner is configured and current', function () {
    config(['ownerable.owner' => FakeOwner::class]);
    $owner = new FakeOwner();
    FakeOwner::setCurrent($owner);

    $calls = [];
    $builder = $this->createMock(Builder::class);
    $builder->method('where')->willReturnCallback(function ($col, $val) use (&$calls) {
        $calls[] = [$col, $val];
    });

    (ScopedFakeOwnerable::$capturedScope)($builder);

    expect($calls)->toBe([
        ['ownerable_id', $owner->getKey()],
        ['ownerable_type', FakeOwner::class],
    ]);
});

test('global scope filters to ownerable_id null when no owner class is configured', function () {
    config(['ownerable.owner' => null]);

    $calls = [];
    $builder = $this->createMock(Builder::class);
    $builder->method('where')->willReturnCallback(function ($col, $val) use (&$calls) {
        $calls[] = [$col, $val];
    });

    (ScopedFakeOwnerable::$capturedScope)($builder);

    expect($calls)->toBe([['ownerable_id', null]]);
});

test('global scope filters to ownerable_id null when getCurrent returns null', function () {
    config(['ownerable.owner' => FakeOwner::class]);
    FakeOwner::setCurrent(null);

    $calls = [];
    $builder = $this->createMock(Builder::class);
    $builder->method('where')->willReturnCallback(function ($col, $val) use (&$calls) {
        $calls[] = [$col, $val];
    });

    (ScopedFakeOwnerable::$capturedScope)($builder);

    expect($calls)->toBe([['ownerable_id', null]]);
});
