<?php

namespace SyncEngine\EventDispatcher\Event;

use Symfony\Contracts\EventDispatcher\Event;

class RenderHookEvent extends Event
{
	public function __construct(
		private string $html
	) {}

	public function getHtml(): string
	{
		return $this->html;
	}

	public function setHtml( string $html ): void
	{
		$this->html = $html;
	}
}
