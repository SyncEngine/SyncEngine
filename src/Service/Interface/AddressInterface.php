<?php

namespace SyncEngine\Service\Interface;

interface AddressInterface extends \CommerceGuys\Addressing\AddressInterface
{
	public function normalize(): array;
}
