<?php

namespace SyncEngine\Service\Generator;

use SyncEngine\Service\Interface\GeneratorInterface;

class Timestamp implements GeneratorInterface
{
	public function generate( string $from = '' ): string
	{
		if ( $from ) {
			return strtotime( $from );
		}
		return time();
	}
}
