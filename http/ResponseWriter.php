<?php

declare(strict_types=1);

namespace http;

interface ResponseWriter extends \io\Writer
{
	public function header(): array;

	public function writeHeader(int $status): void;
}
