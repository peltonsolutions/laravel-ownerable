<?php

use Tests\Fixtures\FakeOwnerable;

test('getOwnerClassname returns null when no owner class is configured', function () {
    config(['ownerable.owner' => null]);

    expect(FakeOwnerable::getOwnerClassname())->toBeNull();
});

test('getOwnerClassname returns null when owner config is an empty string', function () {
    config(['ownerable.owner' => '']);

    expect(FakeOwnerable::getOwnerClassname())->toBeNull();
});

test('getOwnerClassname returns the configured class name', function () {
    config(['ownerable.owner' => 'App\\Models\\Account']);

    expect(FakeOwnerable::getOwnerClassname())->toBe('App\\Models\\Account');
});

test('hasOwner returns false when ownerable_id is null', function () {
    $model = new FakeOwnerable();
    $model->ownerable_id = null;

    expect($model->hasOwner())->toBeFalse();
});

test('hasOwner returns false when ownerable_id is zero', function () {
    $model = new FakeOwnerable();
    $model->ownerable_id = 0;

    expect($model->hasOwner())->toBeFalse();
});

test('hasOwner returns true when ownerable_id is set', function () {
    $model = new FakeOwnerable();
    $model->ownerable_id = 1;

    expect($model->hasOwner())->toBeTrue();
});
