<?php

declare(strict_types=1);

require_once __DIR__.'/../../vendor/autoload.php';

function addAutoloadPath(): void
{
	set_include_path(get_include_path().PATH_SEPARATOR.__DIR__);
	spl_autoload_register();
}

function run(): void
{
	$router = new http\Router('/');
	$app = new App(
		router: $router,
		db: new DbConn(),
	);
	$w = http\F::makeResponseWriter(new \io\Stdout());

	$r = new http\Request(method: 'GET', url: '/users/');
	printf("%s %s\n", $r->method->value, $r->url);
	$app->serve($w, $r);

	echo "\n";

	$r = new http\Request(method: 'GET', url: '/users/4/');
	printf("%s %s\n", $r->method->value, $r->url);
	$app->serve($w, $r);
};

function main(): void
{
	try {
		run();
	} catch (\Throwable $e) {
		error_log((string) $e);
		exit(1);
	}
}

if (PHP_SAPI === 'cli') {
	addAutoloadPath();
	main();
}
