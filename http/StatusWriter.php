<?php

declare(strict_types=1);

namespace http;

interface StatusWriter
{
	public function writeStatus(int $status): void;
}
