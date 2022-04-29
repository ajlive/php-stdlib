<?php

declare(strict_types=1);

namespace http;

use errors\Fatal;

const debug = false;

class Router
{
	public const get = 'get';
	public const post = 'post';

	public function __construct(
		public readonly string $urlRoot,
		public array $getHandlers = [],
		public array $postHandlers = [],
	) {
	}

	public function get(string $urlPattern, Handler $h): void
	{
		$this->getHandlers[$urlPattern] = $h;
	}

	public function post(string $urlPattern, Handler $h): void
	{
		$this->postHandlers[$urlPattern] = $h;
	}

	public function addRoutes(self $otherRouter): void
	{
		$root = $otherRouter->urlRoot;
		foreach ($otherRouter->getHandlers as $urlPattern => $hfunc) {
			$this->getHandlers["{$root}/{$urlPattern}"] = $hfunc;
		}
		foreach ($otherRouter->postHandlers as $urlPattern => $hfunc) {
			$this->postHandlers["{$root}/{$urlPattern}"] = $hfunc;
		}
	}

	public function findHandler(Request $r): ?Handler
	{
		$handler = null;
		$args = [];
		$methodHandlers = Method::GET === $r->method ? $this->getHandlers : $this->postHandlers;
		foreach ($methodHandlers as $urlPattern => $h) {
			$urlPattern = "|^{$this->urlRoot}{$urlPattern}|";
			$urlDoesMatch = preg_match($urlPattern, $r->url, $matches);
			if (PREG_NO_ERROR !== preg_last_error()) {
				throw new Fatal(sprintf('%s, pattern: "%s", url: "%s"', error_get_last()['message'], $urlPattern, $r->url));
			}
			if ($urlDoesMatch) {
				debug && error_log(sprintf('match found: url: "%s", urlPattern: "%s", args: %s', $r->url, $urlPattern, json_encode($args)));
				foreach ($matches as $k => $v) {
					if (!\is_int($k)) {
						$args[$k] = $v;
					}
				}
				$r->setArgs($args);
				$handler = $h;
				break;
			}
			debug && error_log(sprintf('not a match: url: "%s", urlPattern: "%s"', $r->url, $urlPattern));
		}
		return $handler;
	}
}
