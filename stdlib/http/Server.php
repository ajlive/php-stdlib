<?php

declare(strict_types=1);

namespace http;

interface Server
{
	public function responseWriter(): ResponseWriter;
}
