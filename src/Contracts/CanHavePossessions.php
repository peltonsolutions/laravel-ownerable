<?php

namespace PeltonSolutions\LaravelOwnerable\Contracts;

interface CanHavePossessions
{

	static public function getCurrent(): ?self;

	public function addPossession(CanBePossessed $possession);

	public function getKey();

}