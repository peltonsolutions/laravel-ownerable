<?php

namespace Tests\Fixtures;

class NoteWithOwnerableBuilder extends OwnedNote
{
	protected static string $builder = OwnerableCustomBuilder::class;
}
