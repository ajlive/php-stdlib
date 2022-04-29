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
		$verbose = $this->verbose;

		$counts = new Counts();

		$testClassFiles = [];
		foreach ($paths as $path) {
			$testPaths = testFiles($path);
			foreach ($testPaths as $testPath) {
				if (!\in_array($testPath, $testClassFiles, true)) {
					$testClassFiles[] = $testPath;
				}
			}
		}
		sort($testClassFiles);

		if (!$testClassFiles) {
			$this->logger->write("no _test.php files in {$path}\n");
			return;
		}

		foreach ($testClassFiles as $filepath) {
			require_once $filepath;
		}

		foreach (get_declared_classes() as $class) {
			$classParts = explode('\\', $class);
			$classBasename = $classParts[\count($classParts) - 1];
			if (str_starts_with($classBasename, '_')) {
				// skip
				continue;
			}

			if (is_subclass_of($class, T::class, true)) {
				$test = new $class($path, $this->logger, $this->resultsCollector, $counts, $verbose);
				$counts = $test->runTestMethods();
			}
		}

		// print summary
		$this->resultsCollector->write(sprintf(
			"\n%s tests; %s passed; %s failed; %s erred\n",
			$counts->passed + $counts->failed + $counts->erred,
			$counts->passed,
			$counts->failed,
			$counts->erred
		));

		$results = $this->resultsCollector->getResults();
		$this->logger->write("\n\n{$results}");
	}
}

function testFiles(string $rootPath): array
{
	$rootPath = rtrim($rootPath, '/');
	$cmd = "find {$rootPath} -name '*_test.php'";
	$files = [];
	$success = exec(
		command: $cmd,
		output: $files,
		result_code: $code,
	);
	if (!$success) {
		throw new \Exception("command \"{$cmd}\" failed with exit code {$code}");
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
