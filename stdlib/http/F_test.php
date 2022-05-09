<?php

declare(strict_types=1);

namespace http\test;

use testing\T;

class F_test extends T
{
	public function testMakeResponseWriter(): void
	{
		$ioWriter = new F_testWriter();
		$resonseWriter = \http\F::makeResponseWriter($ioWriter);
		$resonseWriter->write('hello');
		$got = $ioWriter->output();
		if ('hello' !== $got) {
			$this->fatalf('wanted: %s, got %s', T::enc('hello'), T::enc($got));
		}
	}
}

class F_testWriter implements \io\Writer
{
	private string $out = '';

	public function write(string $bytes): int
	{
		$this->out .= $bytes;
		return \strlen($bytes);
	}

	public function output(): string
	{
		return $this->out;
	}
}
