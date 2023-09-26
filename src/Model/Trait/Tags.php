<?php

namespace App\Model\Trait;

use App\Model\Interface\Configurable;
use App\Model\Interface\Persistable;

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
