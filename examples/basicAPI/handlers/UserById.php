<?php

declare(strict_types=1);

namespace handlers;

class UserById implements \http\Handler
{
	public const userQuery = <<<'SQL'
select *
  from users
 where id = %d
SQL;

	public function __construct(private readonly \App $app)
	{
	}

	public function serve(\http\ResponseWriter $w, \http\Request $r): void
	{
		$db = $this->app->db;
		['id' => $id] = $r->args;
		$query = sprintf(static::userQuery, $id);
		try {
			$user = $db->query($query);
		} catch (\Throwable $e) {
			$_ = $w->write(json_encode(['error' => $e->getMessage()])."\n");
		}
		$_ = $w->write(json_encode(['user' => $user], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
	}
}
