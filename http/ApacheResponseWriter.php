<?php

declare(strict_types=1);

namespace http;

class ApacheResponseWriter implements ResponseWriter
{
	private readonly \io\Writer $headerWriter;
	private readonly StatusWriter $statusWriter;

	public function __construct(
		private readonly Header $header,
	) {
		$this->headerWriter = new ApacheHeaderWriter();
		$this->statusWriter = new ApacheStatusWriter();
	}

	public function header(): Header
	{
		return $this->header;
	}

	public function writeHeader(int $status): void
	{
		$this->statusWriter->writeStatus($status);
		$this->header->write($this->headerWriter);
	}

	public function write(string $bytes): int
	{
		echo $bytes;
		return \strlen($bytes);
	}
}

class ApacheHeaderWriter implements \io\Writer
{
	public function write(string $bytes): int
	{
		header(header: $bytes, replace: false);
		return \strlen($bytes);
	}
}
