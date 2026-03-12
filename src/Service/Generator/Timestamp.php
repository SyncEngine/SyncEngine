<?php

namespace SyncEngine\Service\Generator;

use SyncEngine\Service\Interface\GeneratorInterface;

class Timestamp implements GeneratorInterface
{
	public function generate( string $from = '', null|int|string $baseTimestamp = null ): string
	{
		if ( is_string( $baseTimestamp ) ) {
			if ( empty( $baseTimestamp ) ) {
				$baseTimestamp = null;
			} else if ( is_numeric( $baseTimestamp ) ) {
				$baseTimestamp = (int) $baseTimestamp;
			} else {
				$baseTimestamp = strtotime( $baseTimestamp );
			}
		}

		if ( $from ) {
			return strtotime( $from, $baseTimestamp );
		}
		return time();
	}
}
