<?php

namespace http;

interface ResponseWriter extends \io\Writer
{
	public function header(): array;

	public function writeHeader(int $status): void;
}
