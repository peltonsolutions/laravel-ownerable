<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
	->in(__DIR__)
	->exclude(['vendor', 'storage', 'bootstrap/cache'])
;

return new Config()
	->setIndent("\t")
	->setLineEnding("\n")
	->setRiskyAllowed(true)
	->setRules([
		'@PHP84Migration' => true,
		// Start from broadly accepted rules, then layer in ones Laravel commonly uses
		'@PhpCsFixer' => true,
		// Keep indentation consistent (uses the indent you set above)
		'indentation_type' => true,
		// Helpful Laravel-ish tweaks
		'array_syntax' => ['syntax' => 'short'],
		'no_unused_imports' => true,
		'ordered_imports' => ['sort_algorithm' => 'alpha'],
		'single_quote' => true,
		'trailing_comma_in_multiline' => ['elements' => ['arrays']],
		'method_chaining_indentation' => true,
		'binary_operator_spaces' => ['operators' => ['=>' => 'align_single_space_minimal']],
		'cast_spaces' => ['space' => 'none'],
		'concat_space' => ['spacing' => 'one'],
		// opt out of a few strict PSR-12 assumptions that fight tabs
		'@PSR12' => true,
	])
	->setFinder($finder);
