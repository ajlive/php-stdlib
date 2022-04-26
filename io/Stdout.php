<?php

namespace io;

class Stdout implements Writer
{
	public function write(string $bytes): int
	{
		$n = fwrite(STDOUT, $bytes);
		if (false === $n) {
			throw new \Exception('failed to write to stdout');
		}
		return $n;
	}
}
