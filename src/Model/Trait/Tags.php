<?php

namespace SyncEngine\Model\Trait;

use SyncEngine\Model\Interface\Configurable;
use SyncEngine\Service\ExecutionContext;

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

	public function getTagsResource( array $config = [], ?ExecutionContext $context = null ): array
	{
		return [ 'config' => $config ];
	}
}
