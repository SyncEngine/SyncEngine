<?php

namespace SyncEngine\Event;

class RenderHookEvent extends HookEvent
{
	public function __construct(
		private string $html
	) {}

	public function getHtml(): string
	{
		return $this->html;
	}

	public function prependHtml( string $html ): void
	{
		$this->html = $html . $this->html;
	}

	public function appendHtml( string $html ): void
	{
		$this->html .= $html;
	}

	public function setHtml( string $html ): void
	{
		$this->html = $html;
	}

	public function getHookType(): string
	{
		return 'render';
	}
}
