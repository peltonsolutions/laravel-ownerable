<?php

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;

/** A model's own builder that does not extend OwnerableBuilder. */
class PlainCustomBuilder extends Builder {}
