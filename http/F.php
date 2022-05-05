<?php

declare(strict_types=1);

namespace http;

abstract class F
{
	public static function makeResponseWriter(\io\Writer $w): ResponseWriter
	{
		return new MadeResponseWriter($w, new Header());
	}

	public static function makeHandler(\Closure $handlerFunc): Handler
	{
		return new MadeHandler($handlerFunc);
	}
}

class MadeResponseWriter implements ResponseWriter
{
	private readonly StatusWriter $statusWriter;

	public function __construct(
		private readonly \io\Writer $w,
		private readonly Header $header,
		?StatusWriter $statusWriter = null,
	) {
		if (null === $statusWriter) {
			$statusWriter = new ApacheStatusWriter();
		}
		$this->statusWriter = $statusWriter;
	}

	public function header(): Header
	{
		return $this->header;
	}

	public function writeHeader(int $status): void
	{
		$this->statusWriter->writeStatus($status);
		$this->header->write($this->w);
	}

	public function write(string $bytes): int
	{
		return $this->w->write($bytes);
	}
}

class MadeHandler implements Handler
{
	public function __construct(
		private \Closure $handlerFunc,
	) {
	}

	public function serve(ResponseWriter $w, Request $r): void
	{
		$handlerFunc = $this->handlerFunc;
		$handlerFunc($w, $r);
	}
}
