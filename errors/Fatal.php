<?php

declare(strict_types=1);

namespace errors;

class Fatal extends \Exception
{
	public function __construct(
		string $message,
		int $code = 1,
		\Throwable|null $previous = null,
	) {
		parent::__construct($message, $code, $previous);
	}
}
