<?php

namespace SyncEngine\Service\Generator;

use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;
use SyncEngine\Service\Interface\GeneratorInterface;

class Token implements GeneratorInterface
{
	public function generate( int $entropy = 256 ): string
	{
		return ( new UriSafeTokenGenerator( $entropy ) )->generateToken();
	}
}
