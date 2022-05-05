<?php

declare(strict_types=1);

class DbConn
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
