<?php

declare(strict_types=1);

namespace http;

interface Handler
{
	public function serve(ResponseWriter $w, Request $r): void;
}
