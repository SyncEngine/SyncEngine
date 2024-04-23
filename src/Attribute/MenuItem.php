<?php

namespace SyncEngine\Attribute;

use Attribute;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class MenuItem
{
	public function __construct(
		protected string $menu,
		protected string $route,
		protected string $label,
		protected string $parent = '',
		protected string $icon = '',
		protected float|int $position = 10,
		protected array $parameters = []
	) {}

	public function setRoute( string $route ): static
	{
		$this->route = $route;
	}

	public function getMenu(): string
	{
		return $this->menu;
	}

	public function getRoute(): string
	{
		return $this->route;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function getIcon(): string
	{
		return $this->icon;
	}

	public function getParent(): string
	{
		return $this->parent;
	}

	public function getPosition(): int|float
	{
		return $this->position;
	}

	public function getParameters(): array
	{
		return $this->parameters;
	}
}
