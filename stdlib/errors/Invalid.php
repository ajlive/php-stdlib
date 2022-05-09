<?php

declare(strict_types=1);

namespace errors;

interface Invalid extends \Throwable
{
	public function isInvalid(): bool;
}
