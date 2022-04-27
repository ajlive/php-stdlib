<?php

declare(strict_types=1);

require_once __DIR__.'/../../vendor/autoload.php';

class ReadUserList implements \http\Handler
{
	public const usersQuery = <<<'SQL'
select * from users
SQL;

	public function __construct(private readonly App $app)
	{
	}

	public function serve(http\ResponseWriter $w, http\Request $r): void
	{
		$db = $this->app->db;
		$users = $db->query(static::usersQuery);
		$_ = $w->write(json_encode(['url' => $r->url, 'users' => $users], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
	}
}

class ReadUser implements \http\Handler
{
	public const userQuery = <<<'SQL'
select *
  from users
 where id = %d
SQL;

	public function __construct(
		private readonly App $app,
		private readonly int $id,
	) {
	}

	public function serve(http\ResponseWriter $w, http\Request $r): void
	{
		$db = $this->app->db;
		$query = sprintf(static::userQuery, $this->id);
		try {
			$user = $db->query($query);
		} catch (\Throwable $e) {
			$_ = $w->write(json_encode(['error' => $e->getMessage()])."\n");
		}
		$_ = $w->write(json_encode(['url' => $r->url, 'user' => $user], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
	}
}

class UsersDb implements \db\Database
{
	public const users = [
		1 => 'Jill',
		2 => 'Chris',
		3 => 'Claire',
		4 => 'Leon',
	];

	public function query(string $query): mixed
	{
		if (preg_match('/where id = (?P<id>\d+)/', $query, $matches)) {
			$id = (int) $matches['id'];
			if (!array_key_exists($id, static::users)) {
				throw new \Exception("no user with id {$id}");
			}
			$name = static::users[$id];
			return ['id' => $id, 'name' => $name];
		}
		return static::users;
	}
}

class App implements \http\Handler
{
	public function __construct(
		public readonly http\Router $router,
		public readonly ?db\Database $db,
	) {
		$this->registerRoutes();
	}

	public function registerRoutes(): void
	{
		$app = $this;
		$router = $this->router;

		$router->get('users/$', fn () => new ReadUserList(app: $app));
		$router->get('users/(?P<id>\d+)/$', fn (string $id) => new ReadUser(app: $app, id: (int) $id));
	}

	public function serve(http\ResponseWriter $w, http\Request $r): void
	{
		$handler = $this->router->findHandler($r);
		$handler->serve($w, $r);
	}
}

function main(): void
{
	$run = function (): void {
		$router = new http\Router('/');
		$app = new App(
			router: $router,
			db: new UsersDb(),
		);
		$r = new http\Request(
			method: 'GET',
			url: '/users/4/',
		);
		$w = http\F::makeResponseWriter(new \io\Stdout());
		$app->serve($w, $r);
	};

	try {
		$run();
	} catch (\Throwable $e) {
		error_log((string) $e);
		exit(1);
	}
}

if (PHP_SAPI === 'cli') {
	main();
}
