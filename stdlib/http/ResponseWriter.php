<?php

declare(strict_types=1);

namespace http;

interface ResponseWriter extends \io\Writer
{
	public function header(): Header;

	public function writeHeader(int $status): void;
}
