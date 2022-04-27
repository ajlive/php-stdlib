<?php

declare(strict_types=1);

namespace clitools;

require_once __DIR__.'/../vendor/autoload.php';

class TestCmd
{
	public static function run(): void
	{
		global $argv;
		$args = match (true) {
			\count($argv) > 1 => \array_slice($argv, 1),
		default => [],
		};

		$verbose = false;
		$paths = [];

		foreach ($args as $arg) {
			if (false === $verbose && '-v' === $arg) {
				$verbose = true;
			} else {
				$paths[] = $arg;
			}
		}
		if ([] === $paths) {
			$paths = ['.'];
		}
		$run = new \testing\Runner(new \io\Stdout(), $verbose);
		$run->all(...$paths);
	}
}

if (\PHP_SAPI === 'cli') {
	TestCmd::run();
}
