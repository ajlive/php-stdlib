<?php

declare(strict_types=1);

namespace testing\test;

use testing\ResultsCollector;
use testing\Runner;
use testing\Runner_testdata\failing\_Failing_test;
use testing\Runner_testdata\failing\_ThrowingAndFailing_test;
use testing\Runner_testdata\passing\_Passing_test;
use testing\T;

/**
 * @internal
 * @coversNothing
 */
final class Runner_test extends T
{
	public function testOutputPassing(): void
	{
		$want = <<<'TXT'
.

1 tests; 1 passed; 0 failed; 0 erred
TXT;

		$logger = new Runner_testLogger();
		$run = new Runner($logger, new ResultsCollector(), verbose: false);
		$run->testClasses(_Passing_test::class);
		$got = $this->cleanGot($logger->got, "\n");
		$failureMsg = $this->compareLineByLine(want: $want, got: $got);
		if (null !== $failureMsg) {
			$this->fatalf($failureMsg);
		}
	}

	public function testOutputPassingVerbose(): void
	{
		$want = <<<'TXT'
ok   testing\Runner_testdata\passing\_Passing_test::testPasses (stdlib/testing/Runner_testdata/passing/_Passing_test.php)

1 tests; 1 passed; 0 failed; 0 erred
TXT;
		$logger = new Runner_testLogger();
		$run = new Runner($logger, new ResultsCollector(), verbose: true);
		$run->testClasses(_Passing_test::class);
		$got = $this->cleanGot($logger->got);
		$failureMsg = $this->compareLineByLine(want: $want, got: $got);
		if (null !== $failureMsg) {
			$this->fatalf($failureMsg);
		}
	}

	public function testOutput(): void
	{
		$want = <<<'TXT'
FFE.

--- FAIL: testing\Runner_testdata\failing\_Failing_test
    --- FAIL: testFails
        stdlib/testing/Runner_testdata/failing/_Failing_test.php:LN: m'test failed!
--- FAIL: testing\Runner_testdata\failing\_ThrowingAndFailing_test
    --- FAIL: testFails
        stdlib/testing/Runner_testdata/failing/_ThrowingAndFailing_test.php:LN: m'test failed!
    --- ERROR: testThrows
        Exception: m'test threw! in stdlib/testing/Runner_testdata/failing/_ThrowingAndFailing_test.php:LN
        Stack trace:
        #0 ... {main}

4 tests; 1 passed; 2 failed; 1 erred
TXT;

		$logger = new Runner_testLogger();
		$run = new Runner($logger, new ResultsCollector(), verbose: false);
		$run->testClasses(_Failing_test::class, _ThrowingAndFailing_test::class, _Passing_test::class);
		$got = $this->cleanGot($logger->got);
		$failureMsg = $this->compareLineByLine(want: $want, got: $got);
		if (null !== $failureMsg) {
			$this->fatalf($failureMsg);
		}
	}

	public function testOutputVerbose(): void
	{
		$want = <<<'TXT'
fail testing\Runner_testdata\failing\_Failing_test::testFails (stdlib/testing/Runner_testdata/failing/_Failing_test.php)
fail testing\Runner_testdata\failing\_ThrowingAndFailing_test::testFails  (stdlib/testing/Runner_testdata/failing/_ThrowingAndFailing_test.php)
err  testing\Runner_testdata\failing\_ThrowingAndFailing_test::testThrows (stdlib/testing/Runner_testdata/failing/_ThrowingAndFailing_test.php)
ok   testing\Runner_testdata\passing\_Passing_test::testPasses (stdlib/testing/Runner_testdata/passing/_Passing_test.php)

--- FAIL: testing\Runner_testdata\failing\_Failing_test
    --- FAIL: testFails
        stdlib/testing/Runner_testdata/failing/_Failing_test.php:LN: m'test failed!
--- FAIL: testing\Runner_testdata\failing\_ThrowingAndFailing_test
    --- FAIL: testFails
        stdlib/testing/Runner_testdata/failing/_ThrowingAndFailing_test.php:LN: m'test failed!
    --- ERROR: testThrows
        Exception: m'test threw! in stdlib/testing/Runner_testdata/failing/_ThrowingAndFailing_test.php:LN
        Stack trace:
        #0 ... {main}

4 tests; 1 passed; 2 failed; 1 erred
TXT;

		$logger = new Runner_testLogger();
		$run = new Runner($logger, new ResultsCollector(), verbose: true);
		$run->testClasses(_Failing_test::class, _ThrowingAndFailing_test::class, _Passing_test::class);
		$got = $this->cleanGot($logger->got);
		$failureMsg = $this->compareLineByLine(want: $want, got: $got);
		if (null !== $failureMsg) {
			$this->fatalf($failureMsg);
		}
	}

	private function cleanGot(string $got): string
	{
		$got = rtrim($got, "\n");
		$got = preg_replace('/#0.*?\{main\}/s', '#0 ... {main}', $got);
		$got = preg_replace('/([\(:])\d+/', '$1LN', $got);
		return $got;
	}
}

class Runner_testLogger implements \io\Writer
{
	public $got = '';

	public function write(string $bytes): int
	{
		$this->got .= $bytes;
		return \strlen($bytes);
	}
}
