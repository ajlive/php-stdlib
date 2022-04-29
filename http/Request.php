<?php

declare(strict_types=1);

namespace http;

enum Method: string
{
	case GET = 'GET';
	case POST = 'POST';
	case HEAD = 'HEAD';
	case OPTIONS = 'OPTIONS';
}

class Request
{
	public function __construct(
		public readonly Method|string $method,
		public readonly string $url,
		public readonly string $queryString = '',
		// ---
		// private
		private readonly array $get = [],
		private readonly array $post = [],
		private readonly array $request = [],
		private readonly array $headers = [],
	) {
		if (\is_string($this->method)) {
			$this->method = Method::from($this->method);
		}
	}

	public static function fromApache(): static
	{
		return new static(
			method: $_SERVER['REQUEST_METHOD'],
			url: $_SERVER['REQUEST_URI'],
			queryString: $_SERVER['QUERY_STRING'],
			get: $_GET,
			post: $_POST,
			request: $_REQUEST,
			headers: getallheaders(),
		);
	}

	public function getVal(string $name): string|array|null
	{
		return $this->get[$name] ?? null;
	}

	public function postVal(string $name): string|array|null
	{
		return $this->post[$name] ?? null;
	}

	public function headerVal(string $name): string|null
	{
		return $this->headers[$name] ?? null;
	}
}
