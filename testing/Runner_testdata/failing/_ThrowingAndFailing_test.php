<?php

declare(strict_types=1);

namespace testing\Runner_testdata\failing;

class _ThrowingAndFailing_test extends \testing\T
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
