<?php

namespace PeltonSolutions\LaravelOwnerable\Contracts;

interface CanHavePossessions
{
	public static function getCurrent(): ?self;

	public function addPossession(CanBePossessed $possession);

	public function getKey();
}
