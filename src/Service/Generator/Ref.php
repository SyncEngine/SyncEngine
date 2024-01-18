<?php

namespace SyncEngine\Service\Generator;

use SyncEngine\Service\Interface\GeneratorInterface;

class Ref implements GeneratorInterface
{
	public function generate(): string
	{
		// Time based.
		$ref  = base_convert( (string) ( time() * 1000 ), 10, 36 );
		// Random.
		$ref .= substr( base_convert( (string) preg_replace( '/\D/', '', rand() / getrandmax() ), 10, 36 ), 1 );

		return $ref;
	}
}
