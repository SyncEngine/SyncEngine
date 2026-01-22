<?php

namespace SyncEngine\Service\Locator;

use SyncEngine\Model\Abstract\ServiceModel;

interface LocatorInterface
{
	public function get( $name ): ?ServiceModel;
	public function getAll(): array;
	public function getTypes(): array;
	public function getNormalized(): array;
}
