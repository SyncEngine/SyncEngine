<?php

namespace SyncEngine\Service\Generator;

use SyncEngine\Service\Interface\GeneratorInterface;

class Microtime implements GeneratorInterface
{
	const FULL = 'full';
	const FLOAT = 'float';

	public function generate( string|bool $format = '' ): string|float
	{
		return match ( $format ) {
			self::FLOAT, true => microtime( true ),
			self::FULL => implode( '.', array_reverse( explode( ' ', substr( microtime(), 2 ) ) ) ),
			default => microtime(),
		};
	}
}
