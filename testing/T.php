<?php

declare(strict_types=1);

namespace testing;

class T
{
	public const indent = '    ';

	private array $failures = [];
	private string $currentTestMethod;
	private bool $subtestFailed;
	private bool $subtestFailedWithError;

	public function __construct(
		protected readonly string $rootPath,
		protected readonly \io\Writer $logger,
		protected readonly Counts $counts,
		protected readonly bool $verbose,
	) {
	}

	public function fatalf(string $format, ...$vs): void
	{
		$jsonVs = [];
		foreach ($vs as $v) {
			if (\is_string($v)) {
				$jsonVs[] = $v;
			} else {
				$jsonV = json_encode($v);
				$jsonV = str_replace('\/', '/', $jsonV);
				$jsonV = str_replace('\\\\', '\\', $jsonV);
				$jsonVs[] = $jsonV;
			}
		}
		throw new Failure('', sprintf($format, ...$jsonVs), null, false);
	}

	public static function enc(string $msg): string
	{
		return json_encode($msg, JSON_UNESCAPED_SLASHES);
	}

	public function run(string $testName, callable $testfunc): void
	{
		try {
			$testfunc();
		} catch (Failure $f) {
			$f->testName = str_replace(' ', '_', $testName);
			$f->isSubtest = true;
			$this->failures[$this->currentTestMethod][] = $f;
			$this->subtestFailed = true;
		} catch (\Throwable $e) {
			$testName = str_replace(' ', '_', $testName);
			$this->failures[$this->currentTestMethod][] = new Failure($testName, $e->getMessage(), $e, true);
			$this->subtestFailed = true;
			$this->subtestFailedWithError = true;
		}
	}

	public function runTestMethods(): Counts
	{
		$workdir = getcwd();

		foreach (get_class_methods(static::class) as $method) {
			if (!str_starts_with($method, 'test')) {
				// skipping non-test method
				continue;
			}

			$this->subtestFailed = false;
			$this->subtestFailedWithError = false;

			$this->currentTestMethod = $method; // set for use with ::run()

			// run method, catch Failures and other errors
			try {
				$this->{$method}();

				$verbose = $this->verbose;
				switch (true) {
					case $this->subtestFailed && $this->subtestFailedWithError:
						$this->counts->erred++;
						if ($verbose) {
							$this->log("E    {$method}\n");
						} else {
							$this->log('E');
						}
						break;
					case $this->subtestFailed:
						$this->counts->failed++;
						if ($verbose) {
							$this->log("F    {$method}\n");
						} else {
							$this->log('F');
						}
						break;
					default:
						$this->counts->passed++;
						if ($verbose) {
							$this->log("ok   {$method}\n");
						} else {
							$this->log('.');
						}
				}
			} catch (Failure $f) {
				$this->failures[$method][] = $f;
				$this->counts->failed++;
				$this->log('F');
			} catch (\Throwable $e) {
				$this->failures[$method][] = new Failure('', $e->getMessage(), $e, false);
				$this->counts->erred++;
				$this->log('E');
			}
		}
		$this->log("\n");

		if ([] === $this->failures) {
			return $this->counts;
		}

		// print failures
		$indent = static::indent;
		$classParts = explode('\\', static::class);
		$relativeClass = implode('\\', \array_slice($classParts, 1, \count($classParts) - 1));
		$this->log(sprintf("--- %s %s\n", $this->emph('FAIL:'), $relativeClass));
		foreach ($this->failures as $method => $testFailures) {
			$hasError = false;
			foreach ($testFailures as $f) {
				if ($f->err) {
					$hasError = true;
					break;
				}
			}
			$prefix = $hasError ? 'ERROR' : 'FAIL';

			$this->log(sprintf("%s--- %s %s\n", $indent, $this->emph("{$prefix}:"), $method));

			foreach ($testFailures as $f) {
				if ($f->isSubtest) {
					$prefix = $f->err ? 'ERROR' : 'FAIL';
					$testHeader = sprintf('%s%s--- %s %s/%s', $indent, $indent, $this->emph("{$prefix}:"), $method, $f->testName);
					$this->log("{$testHeader}\n");
				}

				$trace = $f->getTrace();
				if ($f->err) {
					$trace = $f->err->getTrace();
				}

				$file = '';
				$line = 0;
				foreach ($trace as $_ => $tentry) {
					$replaceCount = 1;
					$file = $tentry['file'];
					if (str_starts_with($file, $workdir)) {
						$file = str_replace($workdir, '', $tentry['file'], $replaceCount);
					}
					$file = trim($file, '/');
					if (str_starts_with($file, $this->rootPath)) {
						$file = str_replace($this->rootPath, '', $file, $replaceCount);
					}
					$file = trim($file, '/');
					$line = $tentry['line'];
					if (!str_contains($file, 'testing.php')) {
						break;
					}
				}

				$msg = sprintf('%s:%s: %s', $file, $line, $f->getMessage());
				if ($f->err) {
					// $msg = sprintf('%s:%s: %s', $f->err->getFile(), $f->err->getLine(), trim((string) $f->err));
					$msg = trim((string) $f->err);
				}

				$msgLines = explode("\n", $msg);
				$msgLinesWithIndent = [];
				foreach ($msgLines as $i => $l) {
					$l = $indent.$l;
					if ($i > 0) {
						$l = $indent.$l;
					}
					if ($f->isSubtest) {
						$l = $indent.$l;
					}
					if ($f->err) {
						$l = str_replace($workdir.'/', '', $l);
					}
					$msgLinesWithIndent[] = $l;
				}

				$msg = implode("\n", $msgLinesWithIndent);
				$this->log("{$indent}{$msg}\n");
			}
		}

		return $this->counts;
	}

	protected function emph(string $msg): string
	{
		return "\033[1m{$msg}\033[m";
	}

	private function log(string $msg): void
	{
		$this->logger->write($msg);
	}
}

class Failure extends \Exception
{
	public $testName = '';
	public $msg = '';
	public $err;
	public $isSubtest = false;

	public function __construct(
		string $testName,
		string $msg,
		?\Throwable $err,
		bool $isSubtest
	) {
		$this->testName = $testName;
		$this->err = $err;
		$this->isSubtest = $isSubtest;
		parent::__construct($msg);
	}
}
