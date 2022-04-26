<?php

namespace http;

abstract class F
{
	public static function serveRoutes(Router $router, Request $r): void
	{
		$handler = $router->findHandler($r);
		if (is_null($handler)) {
			throw new \Exception("no handler for url \"{$r->url}\"");
		}
		$w = $handler->server->w ?? null;
		if (is_null($w)) {
			$w = new DefaultResponseWriter([]);
		}
		$handler->serve($w, $r);
	}

	public static function makeResponseWriter(\io\Writer $w): ResponseWriter
	{
		return new MadeResponseWriter($w, []);
	}
}

class DefaultResponseWriter implements ResponseWriter
{
	public function __construct(
		private array $header,
	) {
	}

	public function header(): array
	{
		return $this->header();
	}

	public function writeHeader(int $status): void
	{
		http_response_code($status);
		foreach ($this->header() as $headerName => $v) {
			header("{$headerName}: {$v}");
		}
	}

	public function write(string $bytes): int
	{
		echo $bytes;
		return strlen($bytes);
	}
}

class MadeResponseWriter implements ResponseWriter
{
	public function __construct(
		private readonly \io\Writer $w,
		private array $header,
	) {
	}

	public function header(): array
	{
		return $this->header;
	}

	public function writeHeader(int $status): void
	{
		http_response_code($status);
		$headerLines = [];
		foreach ($this->header as $headerName => $v) {
			$headerLines[] = "{$headerName}: {$v}";
		}
		$_ = $this->w->write(implode("\n", $headerLines));
	}

	public function write(string $bytes): int
	{
		return $this->w->write($bytes);
	}
}
