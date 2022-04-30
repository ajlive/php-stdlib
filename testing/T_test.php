<?php

declare(strict_types=1);

namespace testing\test;

use testing\Counts;
use testing\ResultsCollector;
use testing\T;

/**
 * @internal
 * @coversNothing
 */
final class T_test extends T
{
	public function testCounts(): void
	{
		$ttest = new _T_testExampleT('testing/', new T_testLogger(), new ResultsCollector(), new Counts(), false);

		// run tests
		$counts = $ttest->runTestMethods();

		// check counts are correct
		$countTests = [
			'count passed correct' => ['got' => $counts->passed, 'want' => 1],
			'count failed correct' => ['got' => $counts->failed, 'want' => 3],
			'count erred correct' => ['got' => $counts->erred, 'want' => 3],
		];
		foreach ($countTests as $name => $tc) {
			$this->run($name, function () use ($tc): void {
				if ($tc['want'] !== $tc['got']) {
					$this->fatalf('count is off: want: %s, got: %s', $tc['want'], $tc['got']);
				}
			});
		}
	}

	public function testLogging(): void
	{
		$logger = new T_testLogger();
		$ttest = new _T_testExampleT('testing/', $logger, new ResultsCollector(), new Counts(), verbose: false);

		// run tests
		$ttest->runTestMethods();

		// check logging
		$want = '.FEFFEE';
		if ($logger->got !== $want) {
			$this->fatalf('wanted: %s, got: %s', T::enc($want), T::enc($logger->got));
		}
	}

	public function testVerboseLogging(): void
	{
		$logger = new T_testLogger();
		$ttest = new _T_testExampleT('testing/', $logger, new ResultsCollector(), new Counts(), verbose: true);

		// run tests
		$ttest->runTestMethods();

		// check logging
		$want = <<<'TXT'
ok   testing\test\_T_testExampleT::testPassingTestPasses                          (testing/T_test.php)
fail testing\test\_T_testExampleT::testFailingTestFails                           (testing/T_test.php)
err  testing\test\_T_testExampleT::testThrowingTestErrs                           (testing/T_test.php)
fail testing\test\_T_testExampleT::testFailingSubtestFailsTest                    (testing/T_test.php)
fail testing\test\_T_testExampleT::testFailureOutsideSubtestFailsTest             (testing/T_test.php)
err  testing\test\_T_testExampleT::testThrowingInSubtestErrsTest                  (testing/T_test.php)
err  testing\test\_T_testExampleT::testThrowingInSubtestAndOutsideSubtestErrsTest (testing/T_test.php)
TXT;
		$got = trim($logger->got, "\n");
		if ($got !== $want) {
			$this->fatalf("wanted:\n%s\ngot:\n%s", $want, $logger->got);
		}
	}

	public function testResults(): void
	{
		$resultsCollector = new ResultsCollector();
		$ttest = new _T_testExampleT('testing/', new T_testLogger(), $resultsCollector, new Counts(), verbose: true);

		// run tests
		$ttest->runTestMethods();

		// check logging
		$want = <<<'TXT'
--- FAIL: test\_T_testExampleT
    --- FAIL: testFailingTestFails
        T_test.php:LN: m'test failed!
    --- ERROR: testThrowingTestErrs
        Exception: m'test threw! in testing/T_test.php:LN
        Stack trace:
        #0 testing/T.php(LN): testing\test\_T_testExampleT->testThrowingTestErrs()
        #1 testing/T_test.php(LN): testing\T->runTestMethods()
        #2 testing/T.php(LN): testing\test\T_test->testResults()
        #3 testing/Runner.php(LN): testing\T->runTestMethods()
        #4 clitools/TestCmd.php(LN): testing\Runner->all(PATHS)
        #5 clitools/TestCmd.php(LN): clitools\TestCmd::run()
        #6 {main}
    --- FAIL: testFailingSubtestFailsTest
        --- FAIL: testFailingSubtestFailsTest/failing_subtest_1
            T_test.php:LN: m'subtest failed!
        --- FAIL: testFailingSubtestFailsTest/failing_subtest_2
            T_test.php:LN: m'subtest failed!
    --- FAIL: testFailureOutsideSubtestFailsTest
        --- FAIL: testFailureOutsideSubtestFailsTest/failing_subtest
            T_test.php:LN: m'subtest failed!
        T_test.php:LN: failed outside subtest
    --- ERROR: testThrowingInSubtestErrsTest
        --- ERROR: testThrowingInSubtestErrsTest/error_in_subtest
            Exception: m'subtest threw! in testing/T_test.php:LN
            Stack trace:
            #0 testing/T.php(LN): testing\test\_T_testExampleT->testing\test\{closure}()
            #1 testing/T_test.php(LN): testing\T->run('error in subtes...', Object(Closure))
            #2 testing/T.php(LN): testing\test\_T_testExampleT->testThrowingInSubtestErrsTest()
            #3 testing/T_test.php(LN): testing\T->runTestMethods()
            #4 testing/T.php(LN): testing\test\T_test->testResults()
            #5 testing/Runner.php(LN): testing\T->runTestMethods()
            #6 clitools/TestCmd.php(LN): testing\Runner->all(PATHS)
            #7 clitools/TestCmd.php(LN): clitools\TestCmd::run()
            #8 {main}
    --- ERROR: testThrowingInSubtestAndOutsideSubtestErrsTest
        --- ERROR: testThrowingInSubtestAndOutsideSubtestErrsTest/error_in_subtest
            Exception: m'subtest threw! in testing/T_test.php:LN
            Stack trace:
            #0 testing/T.php(LN): testing\test\_T_testExampleT->testing\test\{closure}()
            #1 testing/T_test.php(LN): testing\T->run('error in subtes...', Object(Closure))
            #2 testing/T.php(LN): testing\test\_T_testExampleT->testThrowingInSubtestAndOutsideSubtestErrsTest()
            #3 testing/T_test.php(LN): testing\T->runTestMethods()
            #4 testing/T.php(LN): testing\test\T_test->testResults()
            #5 testing/Runner.php(LN): testing\T->runTestMethods()
            #6 clitools/TestCmd.php(LN): testing\Runner->all(PATHS)
            #7 clitools/TestCmd.php(LN): clitools\TestCmd::run()
            #8 {main}
        Exception: then m'test threw outside the subtest! in testing/T_test.php:LN
        Stack trace:
        #0 testing/T.php(LN): testing\test\_T_testExampleT->testThrowingInSubtestAndOutsideSubtestErrsTest()
        #1 testing/T_test.php(LN): testing\T->runTestMethods()
        #2 testing/T.php(LN): testing\test\T_test->testResults()
        #3 testing/Runner.php(LN): testing\T->runTestMethods()
        #4 clitools/TestCmd.php(LN): testing\Runner->all(PATHS)
        #5 clitools/TestCmd.php(LN): clitools\TestCmd::run()
        #6 {main}
TXT;

		// check results

		// check got wanted ouptut
		$failureMsg = $this->compareCharByChar(want: $want, got: $resultsCollector->getResults());
		if (null !== $failureMsg) {
			$this->fatalf($failureMsg);
		}
	}
}

/**
 * @internal
 * @coversNothing
 */
final class _T_testExampleT extends T
{
	public function testPassingTestPasses(): void
	{
		// passed
	}

	public function testFailingTestFails(): void
	{
		$this->fatalf("m'test failed!");
	}

	public function testThrowingTestErrs(): void
	{
		throw new \Exception("m'test threw!");
	}

	public function testFailingSubtestFailsTest(): void
	{
		$tests = [
			'passing subtest' => true,
			'failing subtest 1' => false,
			'failing subtest 2' => false,
		];

		foreach ($tests as $name => $passed) {
			$this->run($name, fn () => $passed ? true : $this->fatalf("m'subtest failed!"));
		}
	}

	public function testFailureOutsideSubtestFailsTest(): void
	{
		$this->run('passing subtest', fn () => true);
		$this->run('failing subtest', fn () => $this->fatalf("m'subtest failed!"));
		$this->fatalf('failed outside subtest');
	}

	public function testThrowingInSubtestErrsTest(): void
	{
		$this->run('error in subtest', fn () => throw new \Exception("m'subtest threw!"));
	}

	public function testThrowingInSubtestAndOutsideSubtestErrsTest(): void
	{
		$this->run('error in subtest', fn () => throw new \Exception("m'subtest threw!"));
		throw new \Exception("then m'test threw outside the subtest!");
	}

	protected function emph(string $msg): string
	{
		return $msg;
	}
}

class T_testLogger implements \io\Writer
{
	public $got = '';

	public function write(string $bytes): int
	{
		$this->got .= $bytes;
		return \strlen($bytes);
	}
}
