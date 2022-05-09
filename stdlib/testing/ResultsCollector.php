<?php

declare(strict_types=1);

namespace testing;

class ResultsCollector implements \io\Writer
{
	private string $results = '';

	public function write(string $bytes): int
	{
		$this->results .= $bytes;
		return \strlen($bytes);
	}

	public function getResults(): string
	{
		return $this->results;
	}
}
