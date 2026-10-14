<?php

namespace SyncEngine\Model\Trait;

use SyncEngine\Model\Interface\Configurable;
use SyncEngine\Runtime\ExecuteContext;

trait Tags
{
	public function getTags(): array
	{
		$tags = [];

		if ( $this instanceof Configurable ) {
			$tags['config'] = [];
		}

		return $tags;
	}

	public function getTagsResource( ?array $config = null, ?ExecuteContext $context = null ): array
	{
		if ( ! isset( $config ) && $this instanceof Configurable ) {
			$config = $this->getConfig();
		}

		return [ 'config' => $config ?? [] ];
	}
}
