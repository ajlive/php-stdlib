<?php

declare(strict_types=1);

namespace db;

interface Record
{
	public function table(): string;

	public function pkField(): string;
}
