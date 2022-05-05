<?php

declare(strict_types=1);

namespace handlers;

class UserList implements \http\Handler
{
	public const usersQuery = <<<'SQL'
select * from users
SQL;

	public function __construct(private readonly \App $app)
	{
	}

	public function serve(\http\ResponseWriter $w, \http\Request $r): void
	{
		$db = $this->app->db;
		$users = $db->query(static::usersQuery);
		$_ = $w->write(json_encode(['users' => $users], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
	}
}
