<?php

declare(strict_types=1);

namespace testing\Runner_testdata\passing;

class _Passing_test extends \testing\T
{
	public function testPasses(): void
	{
	}

	protected function emph(string $msg): string
	{
		return $msg;
	}
}
