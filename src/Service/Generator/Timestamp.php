<?php

namespace SyncEngine\Service\Generator;

use SyncEngine\Service\Interface\GeneratorInterface;

class Timestamp implements GeneratorInterface
{
	public function generate( string $from = '', ?int $baseTimestamp = null ): string
	{
		if ( $from ) {
			return strtotime( $from, $baseTimestamp );
		}
		return time();
	}
}
