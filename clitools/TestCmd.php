<?php

declare(strict_types=1);

namespace clitools;

require_once __DIR__.'/../vendor/autoload.php';

class TestCmdArgs
{
	public function __construct(
		public readonly bool $showHelpAndExit,
		public readonly bool $verbose,
		public readonly array $paths,
	) {
	}
}

class TestCmd
{
	public const usage = <<<'TXT'
composer test -- [options] [<paths>...]
TXT;

	public const help = <<<'TXT'
Description:
    Runs testing\T classes in *_test.php files.

Arguments:
    paths          files containing testing\T classes or dirs to search for *_test.php files (default: ".")

Options:
    -h, --help     show this help message and exit
    -v, --verbose  display verbose output
TXT;

	public static function run(): void
	{
		$args = static::parse();

		if ($args->showHelpAndExit) {
			printf("Usage:\n    %s\n\n%s\n", static::usage, static::help);
			exit;
		}

		$run = new \testing\Runner(new \io\Stdout(), new \testing\ResultsCollector(), $args->verbose);
		try {
			$run->all(...$args->paths);
		} catch (\errors\Invalid $e) {
			if ($e->isInvalid()) {
				echo $e->getMessage()."\n";
				exit(1);
			}
		}
	}

	private static function parse(): TestCmdArgs
	{
		global $argv;
		$args = \array_slice($argv, 1);

		$options = getopt('hv', ['help', 'verbose']);
		$paths = [];
		foreach ($args as $arg) {
			switch (true) {
				case str_starts_with($arg, '-') && !\in_array(ltrim($arg, '-'), array_keys($options), true):
					printf("%s\nunknown option '%s': see --help for a list of valid options\n", static::usage, $arg);
					exit(1);
				case !str_starts_with($arg, '-') && !file_exists($arg):
					printf("%s\npath does not exist: %s\n", static::usage, $arg);
					exit(1);
				case !str_starts_with($arg, '-'):
					$paths[] = $arg;
			}
		}
		if ([] === $paths) {
			$paths = ['.'];
		}

		return new TestCmdArgs(
			showHelpAndExit: isset($options['h']) || isset($options['help']),
			verbose: isset($options['v']) || isset($options['verbose']),
			paths: $paths,
		);
	}
}

if (\PHP_SAPI === 'cli') {
	TestCmd::run();
}
