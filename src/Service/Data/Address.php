<?php

namespace SyncEngine\Service\Data;

use CommerceGuys\Addressing\Address as ImmutableAddress;
use SyncEngine\Service\Interface\AddressInterface;

class Address extends ImmutableAddress implements AddressInterface
{
	public function normalize(): array
	{
		return get_object_vars( $this );
	}
}
