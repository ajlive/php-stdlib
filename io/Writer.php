<?php

declare(strict_types=1);

namespace io;

interface Writer
{
	/**
	 * write writes a string to a resource, string, or other byte buffer.
	 *
	 * It should throw on failure, unlike common write operations like file_put_contents (int|false)
	 *
	 * @throws \Throwable on write failure
	 *
	 * @return int number of bytes written
	 */
	public function write(string $bytes): int;
}
