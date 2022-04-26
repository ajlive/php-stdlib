<?php

$finder = PhpCsFixer\Finder::create()
	->exclude('vendor')
	->in(__DIR__);

$config = new PhpCsFixer\Config();
return $config->setRules([
	'@PhpCsFixer' => true,
	'echo_tag_syntax' => [
		'format' => 'short',
		'shorten_simple_statements_only' => true,
	],
	'no_alternative_syntax' => false,
	'blank_line_before_statement' => false,
	'increment_style' => ['style' => 'post'],
	'return_assignment' => false,
	'no_trailing_whitespace_in_comment' => false,
	'ordered_class_elements' => false,
])
	->setIndent("\t")
	->setFinder($finder);
