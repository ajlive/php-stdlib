<?php

declare(strict_types=1);

class App implements \http\Handler
{
	public function __construct(
		public readonly DbConn $db,
		public readonly http\Router $router,
	) {
		$this->registerRoutes();
	}

	public function registerRoutes(): void
	{
		$app = $this;
		$router = $this->router;

		$router->get('users/$', new handlers\UserList(app: $app));
		$router->get('users/(?P<id>\d+)/$', new handlers\UserById(app: $app));
	}

	public function serve(http\ResponseWriter $w, http\Request $r): void
	{
		$handler = $this->router->findHandler($r);
		$handler->serve($w, $r);
	}
}
