<?php

declare(strict_types=1);

namespace testing;

class Runner
{
	public function __construct(
		private \io\Writer $logger,
		private ResultsCollector $resultsCollector,
		private bool $verbose,
	) {
	}

	public function all(string ...$paths): void
	{
		$testClassFiles = [];
		foreach ($paths as $path) {
			if (!file_exists($path)) {
				throw new \Exception("path does not exist: {$path}");
			}
			if (is_file($path) && str_ends_with($path, '_test.php') && !\in_array($path, $testClassFiles, true)) {
				$testClassFiles[] = $path;
				continue;
			}
			$testPaths = testFiles($path);
			foreach ($testPaths as $testPath) {
				if (!\in_array($testPath, $testClassFiles, true)) {
					$testClassFiles[] = $testPath;
				}
			}
		}
		sort($testClassFiles);
		if ([] === $testClassFiles) {
			$this->logger->write("no _test.php files in {$path}\n");
			return;
		}

		$testClasses = [];
		foreach ($testClassFiles as $filepath) {
			require_once $filepath;
			$classBasename = basename($filepath, '.php');
			foreach (get_declared_classes() as $c) {
				if (str_ends_with($c, $classBasename) && !\in_array($c, $testClasses, true)) {
					$testClasses[] = $c;
				}
			}
		}

		$this->testClasses(...$testClasses);
	}

	public function testClasses(string ...$classes): void
	{
		$verbose = $this->verbose;

		$counts = new Counts();

		foreach ($classes as $class) {
			if (is_subclass_of($class, T::class, true)) {
				$test = new $class($this->logger, $this->resultsCollector, $counts, $verbose);
				$counts = $test->runTestMethods();
			}
		}

		$results = $this->resultsCollector->getResults();

		if ('' !== $results) {
			if (!$this->verbose) {
				$this->logger->write("\n");
			}
			$this->logger->write("\n{$results}");
		}

		// print summary
		if ('' === $results && !$verbose) {
			$this->logger->write("\n");
		}
		$this->logger->write(sprintf(
			"\n%s tests; %s passed; %s failed; %s erred\n",
			$counts->passed + $counts->failed + $counts->erred,
			$counts->passed,
			$counts->failed,
			$counts->erred
		));
	}
}

function testFiles(string $rootPath): array
{
	$rootPath = rtrim($rootPath, '/');
	$cmd = "find {$rootPath} -name '*_test.php'";
	$out = [];
	$success = exec(
		command: $cmd,
		output: $out,
		result_code: $code,
	);
	if (!$success) {
		throw new \Exception("command \"{$cmd}\" failed with exit code {$code}");
	}

	$files = [];
	foreach ($out as $file) {
		if (!str_starts_with(basename($file), '_')) {
			$files[] = $file;
		}
	}
	return $files;
}

class SubtestFailure extends \Exception
{
	public $hadErrors = false;

	public function __construct(bool $hadErrors)
	{
		$this->hadErrors = $hadErrors;
	}
}
