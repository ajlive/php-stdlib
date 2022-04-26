<?php

namespace db;

interface Record
{
	public function table(): string;

	public function pkField(): string;
}
