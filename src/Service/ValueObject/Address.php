<?php

namespace SyncEngine\Service\ValueObject;

use CommerceGuys\Addressing\Address as ImmutableAddress;
use SyncEngine\Service\Interface\AddressInterface;
use SyncEngine\Service\ModelNormalizer;

class Address extends ImmutableAddress implements AddressInterface
{
	public function normalize(): array
	{
		return ( new ModelNormalizer() )->normalize( $this );
	}
}
