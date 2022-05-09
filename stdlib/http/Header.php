<?php

declare(strict_types=1);

namespace http;

class Header
{
	private array $header = [];

	public function add(string $k, string $v): void
	{
		$this->header[$k][] = $v;
	}

	public function values(string $k): array
	{
		return $this->header[$k] ?? [];
	}

	public function write(\io\Writer $w): void
	{
		foreach ($this->headers as $k => $values) {
			foreach ($values as $v) {
				$w->write("{$k}: {$v}");
			}
		}
	}
}
