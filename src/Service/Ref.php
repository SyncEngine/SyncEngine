<?php

namespace SyncEngine\Service;

class Ref
{
	public function create(): string
	{
		// Time based.
		$ref  = base_convert( (string) ( time() * 1000 ), 10, 36 );
		// Random.
		$ref .= substr( base_convert( (string) preg_replace( '/\D/', '', rand() / getrandmax() ), 10, 36 ), 1 );

		return $ref;
	}
}
