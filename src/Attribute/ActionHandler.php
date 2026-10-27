<?php

namespace SyncEngine\Attribute;

use Attribute;

#[Attribute]
class ActionHandler
{
	public function __construct(
		public readonly string $action,
		public readonly string $permission = 'ROLE_EDITOR',
	) {}
}
