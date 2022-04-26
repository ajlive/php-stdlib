<?php

namespace db;

interface Database
{
	public function query(string $query): mixed;
}
