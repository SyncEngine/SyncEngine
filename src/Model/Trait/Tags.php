<?php

namespace SyncEngine\Model\Trait;

use SyncEngine\Model\Interface\Configurable;
use SyncEngine\Model\Interface\Persistable;

trait Tags
{
	public function getTags(): array
	{
		$tags = [];

		if ( $this instanceof Configurable ) {
			$tags[ 'config' ] = [];
		}

		return $tags;
	}

	public function getTagsResource( array $config ): array
	{
		return [ 'config' => $config ];
	}
}
