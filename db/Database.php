<?php

declare(strict_types=1);

namespace db;

interface Database
{
	public function query(string $query): mixed;
}
