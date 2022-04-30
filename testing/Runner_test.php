<?php

declare(strict_types=1);

namespace testing\test;

use errors\Fatal;
use testing\T;

/**
 * @internal
 * @coversNothing
 */
final class Runner_test extends T
{
	public function testOutputPassing(): void
	{
		$this->makeTestFiles();
		$want = <<<'TXT'
.

1 tests; 1 passed; 0 failed; 0 erred
TXT;

		$got = shell_exec('composer test -- /tmp/Runner_test/passing/Passing_test.php 2>/dev/null'); // 2>/dev/null silences composer saying which script it ran
		$failureMsg = $this->compareCharByChar(want: $want, got: $got);
		if (null !== $failureMsg) {
			$this->fatalf($failureMsg);
		}
		$this->removeTestFiles();
	}

	public function testOutputPassingVerbose(): void
	{
		$this->makeTestFiles();
		$want = <<<'TXT'
ok   passing\Passing_test::testPasses (private/tmp/Runner_test/passing/Passing_test.php)

1 tests; 1 passed; 0 failed; 0 erred
TXT;

		$got = shell_exec('composer test -- /tmp/Runner_test/passing/Passing_test.php -v 2>/dev/null'); // 2>/dev/null silences composer saying which script it ran
		$failureMsg = $this->compareCharByChar(want: $want, got: $got);
		if (null !== $failureMsg) {
			$this->fatalf($failureMsg);
		}
		$this->removeTestFiles();
	}

	public function testOutput(): void
	{
		$this->makeTestFiles();
		$want = <<<'TXT'
FFE.

--- FAIL: Failing_test
    --- FAIL: testFails
        private/tmp/Runner_test/failing/Failing_test.php:LN: m'test failed!
--- FAIL: ThrowingAndFailng_test
    --- FAIL: testFails
        private/tmp/Runner_test/failing/ThrowingAndFailng_test.php:LN: m'test failed!
    --- ERROR: testThrows
        Exception: m'test threw! in /private/tmp/Runner_test/failing/ThrowingAndFailng_test.php:LN
        Stack trace:
        #0 testing/T.php(LN): failing\ThrowingAndFailng_test->testThrows()
        #1 testing/Runner.php(LN): testing\T->runTestMethods()
        #2 clitools/TestCmd.php(LN): testing\Runner->all(PATHS)
        #3 clitools/TestCmd.php(LN): clitools\TestCmd::run()
        #4 {main}

4 tests; 1 passed; 2 failed; 1 erred
TXT;

		$got = shell_exec('composer test -- /tmp/Runner_test/ 2>/dev/null');
		$failureMsg = $this->compareCharByChar(want: $want, got: $got);
		if (null !== $failureMsg) {
			$this->fatalf($failureMsg);
		}
		$this->removeTestFiles();
	}

	public function testOutputVerbose(): void
	{
		$this->makeTestFiles();
		$want = <<<'TXT'
fail failing\Failing_test::testFails (private/tmp/Runner_test/failing/Failing_test.php)
fail failing\ThrowingAndFailng_test::testFails  (private/tmp/Runner_test/failing/ThrowingAndFailng_test.php)
err  failing\ThrowingAndFailng_test::testThrows (private/tmp/Runner_test/failing/ThrowingAndFailng_test.php)
ok   passing\Passing_test::testPasses (private/tmp/Runner_test/passing/Passing_test.php)

--- FAIL: Failing_test
    --- FAIL: testFails
        private/tmp/Runner_test/failing/Failing_test.php:LN: m'test failed!
--- FAIL: ThrowingAndFailng_test
    --- FAIL: testFails
        private/tmp/Runner_test/failing/ThrowingAndFailng_test.php:LN: m'test failed!
    --- ERROR: testThrows
        Exception: m'test threw! in /private/tmp/Runner_test/failing/ThrowingAndFailng_test.php:LN
        Stack trace:
        #0 testing/T.php(LN): failing\ThrowingAndFailng_test->testThrows()
        #1 testing/Runner.php(LN): testing\T->runTestMethods()
        #2 clitools/TestCmd.php(LN): testing\Runner->all(PATHS)
        #3 clitools/TestCmd.php(LN): clitools\TestCmd::run()
        #4 {main}

4 tests; 1 passed; 2 failed; 1 erred
TXT;

		$got = shell_exec('composer test -- /tmp/Runner_test/ -v 2>/dev/null');
		$failureMsg = $this->compareCharByChar(want: $want, got: $got);
		if (null !== $failureMsg) {
			$this->fatalf($failureMsg);
		}
		$this->removeTestFiles();
	}

	public const Passing_test = <<<'PHP'
<?php

namespace passing;

class Passing_test extends \testing\T
{
	public function testPasses(): void
	{
	}

	protected function emph(string $msg): string
	{
		return $msg;
	}
}
PHP;

	public const Failing_test = <<<'PHP'
<?php

namespace failing;

class Failing_test extends \testing\T
{
	public function testFails(): void
	{
		$this->fatalf("m'test failed!");
	}

	protected function emph(string $msg): string
	{
		return $msg;
	}
}
PHP;

	public const ThrowingAndFailng_test = <<<'PHP'
<?php

namespace failing;

class ThrowingAndFailng_test extends \testing\T
{
	public function testFails(): void
	{
		$this->fatalf("m'test failed!");
	}

	public function testThrows(): void
	{
		throw new \Exception("m'test threw!");
	}

	protected function emph(string $msg): string
	{
		return $msg;
	}
}
PHP;

	public function makeTestFiles(): void
	{
		if (is_dir('/tmp/Runner_test')) {
			$this->removeTestFiles();
		}
		$cmd = 'mkdir -p /tmp/Runner_test/passing && mkdir /tmp/Runner_test/failing';
		system($cmd, $resultCode);
		if (0 !== $resultCode) {
			throw new Fatal("failed to make test file directory with command: '{$cmd}'");
		}
		file_put_contents('/tmp/Runner_test/passing/Passing_test.php', static::Passing_test);
		file_put_contents('/tmp/Runner_test/failing/Failing_test.php', static::Failing_test);
		file_put_contents('/tmp/Runner_test/failing/ThrowingAndFailng_test.php', static::ThrowingAndFailng_test);
	}

	public function removeTestFiles(): void
	{
		if (!is_dir('/tmp/Runner_test')) {
			return;
		}
		$cmd = 'rm -rf /tmp/Runner_test';
		system($cmd, $resultCode);
		if (0 !== $resultCode) {
			throw new Fatal("failed to remove test file directory with command: '{$cmd}'");
		}
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
