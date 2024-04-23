<?php

namespace SyncEngine\Service\Menu;

use SyncEngine\Attribute\MenuItem;

class MenuItemCollection
{
	/**
	 * @var MenuItem[]
	 */
	private array $collection = [];

	public function __construct() {}

	public function add( MenuItem $item ): self
	{
		$this->collection[] = $item;

		return $this;
	}

	/**
	 * @return MenuItem[]
	 */
	public function all(): array
	{
		return $this->collection;
	}
}
