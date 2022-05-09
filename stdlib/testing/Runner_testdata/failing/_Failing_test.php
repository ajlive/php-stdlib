<?php

declare(strict_types=1);

namespace testing\Runner_testdata\failing;

class _Failing_test extends \testing\T
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
