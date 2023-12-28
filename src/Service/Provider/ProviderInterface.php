<?php

namespace SyncEngine\Service\Provider;

use SyncEngine\Model\Abstract\ServiceModel;

interface ProviderInterface
{
	public function get( $name ): ?ServiceModel;
	public function getAll(): array;
	public function getTypes(): array;
	public function getNormalized(): array;
}
