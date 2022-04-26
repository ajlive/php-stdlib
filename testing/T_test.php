<?php

declare(strict_types=1);

namespace testing;

/**
 * @internal
 * @coversNothing
 */
class TTest extends T
{
	public function testOutput(): void
	{
		$logger = new MockLog();
		$ttest = new _TTest('testing/', $logger, new Counts(), $this->verbose);

		// run _TTest
		$counts = $ttest->runTestMethods();

		// check counts are correct
		$countTests = [
			'count passed correct' => ['got' => $counts->passed, 'want' => 1],
			'count failed correct' => ['got' => $counts->failed, 'want' => 3],
			'count erred correct' => ['got' => $counts->erred, 'want' => 3],
		];
		foreach ($countTests as $name => $tc) {
			$this->run($name, function () use ($tc) {
				if ($tc['want'] !== $tc['got']) {
					$this->fatalf('count is off: want: %s, got: %s', $tc['want'], $tc['got']);
				}
			});
		}

		// ---
		// check output

		// prepare output for checking
		$want = <<<'OUT'
.FEFFEE
--- FAIL: _TTest
    --- FAIL: testFailure
        T_test.php:LN: mock ordinary failure
    --- ERROR: testError
        Exception: mock error in testing/T_test.php:LN
        Stack trace:
        #0 testing/T.php(LN): testing\_TTest->testError()
        #1 testing/T_test.php(LN): testing\T->runTestMethods()
        #2 testing/T.php(LN): testing\TTest->testOutput()
        #3 testing/Runner.php(LN): testing\T->runTestMethods()
        #4 clitools/TestCmd.php(LN): testing\Runner->all(PATHS)
        #5 clitools/TestCmd.php(LN): clitools\TestCmd::run()
        #6 {main}
    --- FAIL: testSubtests
        --- FAIL: testSubtests/wrong_sep
            T_test.php:LN: want: ["a","b"], got: ["a/b/c"]
        --- FAIL: testSubtests/trailing_sep
            T_test.php:LN: want: ["b"], got: ["a","b","c",""]
    --- FAIL: testSubtestsAndFailureOutsideSubtest
        --- FAIL: testSubtestsAndFailureOutsideSubtest/wrong_sep
            T_test.php:LN: want: ["a","b"], got: ["a/b/c"]
        --- FAIL: testSubtestsAndFailureOutsideSubtest/trailing_sep
            T_test.php:LN: want: ["b"], got: ["a","b","c",""]
        T_test.php:LN: mock failure outside subtest
    --- ERROR: testErrorInSubtest
        --- FAIL: testErrorInSubtest/failing_test_1
            T_test.php:LN: want: ["1","2"], got: ["1","2","3"]
        --- ERROR: testErrorInSubtest/mock_error_in_subtest
            Exception: mock error in testing/T_test.php:LN
            Stack trace:
            #0 testing/T.php(LN): testing\_TTest->testErrorInSubtest()
            #1 testing/T_test.php(LN): testing\T->runTestMethods()
            #2 testing/T.php(LN): testing\TTest->testOutput()
            #3 testing/Runner.php(LN): testing\T->runTestMethods()
            #4 clitools/TestCmd.php(LN): testing\Runner->all(PATHS)
            #5 clitools/TestCmd.php(LN): clitools\TestCmd::run()
            #6 {main}
        --- FAIL: testErrorInSubtest/faling_test_2
            T_test.php:LN: want: ["3"], got: ["1","2","3",""]
    --- ERROR: testErrorInSubtestAndErrorOutsideSubtest
        --- FAIL: testErrorInSubtestAndErrorOutsideSubtest/failing_test_1
            T_test.php:LN: want: ["1","2"], got: ["1","2","3"]
        --- ERROR: testErrorInSubtestAndErrorOutsideSubtest/mock_error_in_subtest
            Exception: mock error in testing/T_test.php:LN
            Stack trace:
            #0 testing/T.php(LN): testing\_TTest->testErrorInSubtestAndErrorOutsideSubtest()
            #1 testing/T_test.php(LN): testing\T->runTestMethods()
            #2 testing/T.php(LN): testing\TTest->testOutput()
            #3 testing/Runner.php(LN): testing\T->runTestMethods()
            #4 clitools/TestCmd.php(LN): testing\Runner->all(PATHS)
            #5 clitools/TestCmd.php(LN): clitools\TestCmd::run()
            #6 {main}
        --- FAIL: testErrorInSubtestAndErrorOutsideSubtest/faling_test_2
            T_test.php:LN: want: ["3"], got: ["1","2","3",""]
        Exception: mock error in testing/T_test.php:LN
        Stack trace:
        #0 testing/T.php(LN): testing\_TTest->testErrorInSubtestAndErrorOutsideSubtest()
        #1 testing/T_test.php(LN): testing\T->runTestMethods()
        #2 testing/T.php(LN): testing\TTest->testOutput()
        #3 testing/Runner.php(LN): testing\T->runTestMethods()
        #4 clitools/TestCmd.php(LN): testing\Runner->all(PATHS)
        #5 clitools/TestCmd.php(LN): clitools\TestCmd::run()
        #6 {main}
OUT;
		$got = preg_replace('/([\(:])\d+/', '$1LN', $logger->got);
		$got = preg_replace('/\d+([\):])/', 'LN$1', $got);
		$got = preg_replace('/Runner->all\(.*?\)/', 'Runner->all(PATHS)', $got);
		$got = trim($got);

		$want = trim($want);

		// check got wanted ouptut
		$context = '';
		$badIndex = 0;
		for ($i = 0; $i < strlen($want); $i++) {
			$c = $want[$i];
			$gc = $got[$i];
			if ($gc !== $c) {
				$context = substr($context, -50);
				$context = "\"{$context}{$gc}\": want: '{$c}', got: '{$gc}'";
				$badIndex = $i;
				$this->fatalf("want:\n%s,\n got:\n%s,\nerror at char {$badIndex}: %s", $want, $got, $context);
				break;
			}
			if ("\n" === $c) {
				$c = '\n';
			}
			$context .= $c;
		}
	}
}

/**
 * @internal
 * @coversNothing
 */
class _TTest extends T
{
	public function testPassing(): void
	{
		// passed
	}

	public function testFailure(): void
	{
		$this->fatalf('mock ordinary failure');
	}

	public function testError(): void
	{
		throw new \Exception('mock error');
	}

	public function testSubtests(): void
	{
		$tests = [
			'simple' => ['input' => 'a/b/c', 'sep' => '/', 'want' => ['a', 'b', 'c']],
			'wrong sep' => ['input' => 'a/b/c', 'sep' => ',', 'want' => ['a', 'b']],
			'trailing sep' => ['input' => 'a/b/c/', 'sep' => '/', 'want' => ['b']],
		];

		foreach ($tests as $name => $tc) {
			$this->run($name, function () use ($tc) {
				$got = explode($tc['sep'], $tc['input']);
				if ($tc['want'] !== $got) {
					$this->fatalf('want: %s, got: %s', $tc['want'], $got);
				}
			});
		}
	}

	public function testSubtestsAndFailureOutsideSubtest(): void
	{
		$tests = [
			'simple' => ['input' => 'a/b/c', 'sep' => '/', 'want' => ['a', 'b', 'c']],
			'wrong sep' => ['input' => 'a/b/c', 'sep' => ',', 'want' => ['a', 'b']],
			'trailing sep' => ['input' => 'a/b/c/', 'sep' => '/', 'want' => ['b']],
		];

		foreach ($tests as $name => $tc) {
			$this->run($name, function () use ($tc) {
				$got = explode($tc['sep'], $tc['input']);
				if ($tc['want'] !== $got) {
					$this->fatalf('want: %s, got: %s', $tc['want'], $got);
				}
			});
		}

		$this->fatalf('mock failure outside subtest');
	}

	public function testErrorInSubtest(): void
	{
		$tests = [
			'failing test 1' => ['input' => '1|2|3', 'sep' => '|', 'want' => ['1', '2']],
			'mock error in subtest' => new \Exception('mock error'),
			'faling test 2' => ['input' => '1|2|3|', 'sep' => '|', 'want' => ['3']],
		];

		foreach ($tests as $name => $tc) {
			$this->run($name, function () use ($tc) {
				if ($tc instanceof \Throwable) {
					throw $tc;
				}

				$got = explode($tc['sep'], $tc['input']);
				if ($tc['want'] !== $got) {
					$this->fatalf('want: %s, got: %s', $tc['want'], $got);
				}
			});
		}
	}

	public function testErrorInSubtestAndErrorOutsideSubtest(): void
	{
		$tests = [
			'failing test 1' => ['input' => '1|2|3', 'sep' => '|', 'want' => ['1', '2']],
			'mock error in subtest' => new \Exception('mock error'),
			'faling test 2' => ['input' => '1|2|3|', 'sep' => '|', 'want' => ['3']],
		];

		foreach ($tests as $name => $tc) {
			$this->run($name, function () use ($tc) {
				if ($tc instanceof \Throwable) {
					throw $tc;
				}

				$got = explode($tc['sep'], $tc['input']);
				if ($tc['want'] !== $got) {
					$this->fatalf('want: %s, got: %s', $tc['want'], $got);
				}
			});
		}

		throw new \Exception('mock error');
	}

	protected function emph(string $msg): string
	{
		return $msg;
	}
}

class MockLog implements \io\Writer
{
	public $got = '';

	public function write(string $bytes): int
	{
		$this->got .= $bytes;
		return strlen($bytes);
	}
}
