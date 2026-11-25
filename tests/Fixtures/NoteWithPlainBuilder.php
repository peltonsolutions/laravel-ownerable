<?php

namespace Tests\Fixtures;

class NoteWithPlainBuilder extends OwnedNote
{
	protected static string $builder = PlainCustomBuilder::class;
}
