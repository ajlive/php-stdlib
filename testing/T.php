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
		protected readonly ResultsCollector $resultsCollector,
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
		$verbose = $this->verbose;
		$workdir = getcwd();
		$r = new \ReflectionClass(static::class);
		$testMethods = array_filter(get_class_methods($this), fn ($m) => str_starts_with($m, 'test'));

		$methodPath = fn (string $method): string => sprintf('%s::%s', $r->getName(), $method);

		// $maxMethodPathLen = array_reduce($testMethods, fn (string|null $carry, string $item) => (\strlen($item) > \strlen($carry) ? $item : $carry));
		$maxMethodPathLen = 0;
		foreach ($testMethods as $m) {
			$mPathLen = \strlen($methodPath($m));
			if ($mPathLen > $maxMethodPathLen) {
				$maxMethodPathLen = $mPathLen;
			}
		}

		$logShortVerbose = function (string $result, string $method) use ($r, $methodPath, $maxMethodPathLen): void {
			$fileRelPath = ltrim(str_replace(getcwd(), '', $r->getFileName()), '/');
			$msg = sprintf("%s%s(%s)\n", str_pad($result, 5), str_pad($methodPath($method), $maxMethodPathLen + 1), $fileRelPath);
			$this->logNow($msg);
		};

		foreach ($testMethods as $method) {
			$this->subtestFailed = false;
			$this->subtestFailedWithError = false;

			$this->currentTestMethod = $method; // set for use with ::run()

			// run method, catch Failures and other errors
			try {
				$this->{$method}();
				switch (true) {
					case $this->subtestFailed && $this->subtestFailedWithError:
						$this->counts->erred++;
						if ($verbose) {
							$logShortVerbose('err', $method);
						} else {
							$this->logNow('E');
						}
						break;
					case $this->subtestFailed:
						$this->counts->failed++;
						if ($verbose) {
							$logShortVerbose('fail', $method);
						} else {
							$this->logNow('F');
						}

						break;
					default:
						$this->counts->passed++;
						if ($verbose) {
							$logShortVerbose('ok', $method);
						} else {
							$this->logNow('.');
						}
				}
			} catch (Failure $f) {
				$this->failures[$method][] = $f;
				$this->counts->failed++;
				if ($verbose) {
					$logShortVerbose('fail', $method);
				} else {
					$this->logNow('F');
				}
			} catch (\Throwable $e) {
				$this->failures[$method][] = new Failure('', $e->getMessage(), $e, false);
				$this->counts->erred++;
				if ($verbose) {
					$logShortVerbose('err', $method);
				} else {
					$this->logNow('E');
				}
			}
		}

		if ([] === $this->failures) {
			return $this->counts;
		}

		// print failures
		$indent = static::indent;
		$classParts = explode('\\', static::class);
		$relativeClass = implode('\\', \array_slice($classParts, 1, \count($classParts) - 1));
		$this->collectResults(sprintf("--- %s %s\n", $this->emph('FAIL:'), $relativeClass));
		foreach ($this->failures as $method => $testFailures) {
			$hasError = false;
			foreach ($testFailures as $f) {
				if ($f->err) {
					$hasError = true;
					break;
				}
			}
			$prefix = $hasError ? 'ERROR' : 'FAIL';

			$this->collectResults(sprintf("%s--- %s %s\n", $indent, $this->emph("{$prefix}:"), $method));

			foreach ($testFailures as $f) {
				if ($f->isSubtest) {
					$prefix = $f->err ? 'ERROR' : 'FAIL';
					$testHeader = sprintf('%s%s--- %s %s/%s', $indent, $indent, $this->emph("{$prefix}:"), $method, $f->testName);
					$this->collectResults("{$testHeader}\n");
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
				$this->collectResults("{$indent}{$msg}\n");
			}
		}

		return $this->counts;
	}

	protected function emph(string $msg): string
	{
		return "\033[1m{$msg}\033[m";
	}

	private function logNow(string $msg): void
	{
		$this->logger->write($msg);
	}

	private function collectResults(string $msg): void
	{
		$this->resultsCollector->write($msg);
	}

	protected function compareCharByChar(string $want, string $got): ?string
	{
		$got = preg_replace('/([\(:])\d+/', '$1LN', $got);
		$got = preg_replace('/\d+([\):])/', 'LN$1', $got);
		$got = preg_replace('/Runner->all\(.*?\)/', 'Runner->all(PATHS)', $got);
		$got = trim($got, "\n");
		// error_log("##\n{$got}\n##");
		// exit;

		$want = trim($want, "\n");
		$context = '';
		$badIndex = 0;
		for ($i = 0; $i < \strlen($want); $i++) {
			$c = $want[$i];
			$gc = $got[$i];
			if ($gc !== $c) {
				if (preg_match('/\s/', $gc)) {
					$gc = json_encode($gc);
				}
				if (preg_match('/\s/', $c)) {
					$c = json_encode($c);
				}
				$context = substr($context, -50);
				$context = "\"{$context}{$gc}\": want: '{$c}', got: '{$gc}'";
				$badIndex = $i;
				return sprintf("want:\n%s,\n got:\n%s,\nerror at char {$badIndex}: %s", $want, $got, $context);
			}
			if ("\n" === $c) {
				$c = '\n';
			}
			$context .= $c;
		}
		return null;
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
