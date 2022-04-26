<?php

namespace http;

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

	public function get(string $urlPattern, \Closure $hfunc): void
	{
		$this->getHandlers[$urlPattern] = $hfunc;
	}

	public function post(string $urlPattern, \Closure $hfunc): void
	{
		$this->postHandlers[$urlPattern] = $hfunc;
	}

	public function addRoutes(Router $otherRouter): void
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
		foreach ($methodHandlers as $urlPattern => $hfunc) {
			$urlPattern = "|^{$this->urlRoot}{$urlPattern}|";
			if (preg_match($urlPattern, $r->url, $matches)) {
				foreach ($matches as $k => $v) {
					if (!is_int($k)) {
						$args[$k] = $v;
					}
				}
				debug && error_log(sprintf('match found: url: "%s", urlPattern: "%s", args: %s', $r->url, $urlPattern, json_encode($args)));
				$handler = $hfunc(...$args);
				break;
			}
			debug && error_log(sprintf('not a match: url: "%s", urlPattern: "%s"', $r->url, $urlPattern));
		}
		return $handler;
	}
}
