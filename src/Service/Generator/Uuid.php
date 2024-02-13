<?php

namespace SyncEngine\Service\Generator;

use Symfony\Component\Uid\UuidV7;
use SyncEngine\Service\Interface\GeneratorInterface;

class Uuid implements GeneratorInterface
{
	public function generate(): string
	{
		return UuidV7::generate();
	}
}
