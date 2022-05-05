<?php

declare(strict_types=1);

namespace http;

class ApacheStatusWriter implements StatusWriter
{
	public function writeStatus(int $status): void
	{
		http_response_code($status);
	}
}
