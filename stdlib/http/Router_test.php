<?php

declare(strict_types=1);

namespace http\test;

use errors\Fatal;
use http\ApacheResponseWriter;
use http\Header;
use http\Request;
use http\ResponseWriter;
use http\Router;
use testing\T;

/**
 * @internal
 * @coversNothing
 */
final class Router_test extends T
{
	public function testRoute(): void
	{
		$router = new Router('/');
		$router->get('login/$', \http\F::makeHandler(handleLogin(...)));
		$r = new Request('GET', '/login/');
		$got = servedOutput($router, $r);
		$want = "logging in at '/login/'";
		if ($got !== $want) {
			$this->fatalf('wanted: %s, got: %s', T::enc($want), T::enc($got));
		}
	}

	public function testNoRout(): void
	{
		$router = new Router('/');
		$router->get('login/$', \http\F::makeHandler(handleLogin(...)));
		$r = new Request('GET', '/login');
		$handler = $router->findHandler($r);
		if (null !== $handler) {
			$this->fatalf("found handler for bad url '%s'", $r->url);
		}
	}

	public function testAddRoutes(): void
	{
		$router = new Router('/app/');
		$router->get('login/$', \http\F::makeHandler(handleLogin(...)));
		$routerAPI = new Router('api');
		$routerAPI->get('users/(?P<id>\d+)/$', \http\F::makeHandler(handleUserByID(...)));

		$router->addRoutes($routerAPI);

		$r = new Request('GET', '/app/login/');
		$got = servedOutput($router, $r);
		$want = "logging in at '/app/login/'";
		if ($got !== $want) {
			$this->fatalf('wanted: %s, got: %s', T::enc($want), T::enc($got));
		}

		$r = new Request('GET', '/app/api/users/42/');
		$got = servedOutput($router, $r);
		$want = 'got user with id 42';
		if ($got !== $want) {
			$this->fatalf('wanted: %s, got: %s', T::enc($want), T::enc($got));
		}
	}

	public function testBadUrlPattern(): void
	{
		$router = new Router('/');
		$badPattern = 'users/(?P<id\d+)/$'; // missing terminator ">" after "<id"
		$router->get($badPattern, \http\F::makeHandler(handleUserByID(...)));

		// turn off warnings to suppress preg_match in stdout
		$errorLevel = error_reporting();
		error_reporting(0);

		try {
			$router->findHandler(new Request('GET', '/login/'));
			$this->fatalf('should have thrown %s for bad url pattern "%s"', Fatal::class, $badPattern);
		} catch (Fatal $e) {
			$want = 'syntax error in subpattern name (missing terminator?)';
			$got = $e->getMessage();
			if (!str_contains($got, $want)) {
				$this->fatalf('wanted error message containing: %s, got: %s', T::enc($want), T::enc($got));
			}
		} finally {
			// set error level back to previous level
			error_reporting($errorLevel);
		}
	}
}

function handleLogin(ResponseWriter $w, Request $r): void
{
	$w->write("logging in at '{$r->url}'");
}

function handleUserByID(ResponseWriter $w, Request $r): void
{
	['id' => $id] = $r->args;
	$w->write("got user with id {$id}");
}

function servedOutput(Router $router, Request $r): string
{
	$handler = $router->findHandler($r);
	$w = new ApacheResponseWriter(new Header());
	ob_start();
	$handler->serve($w, $r);
	return ob_get_clean();
}
