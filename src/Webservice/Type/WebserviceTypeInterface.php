<?php

namespace SyncEngine\Webservice\Type;

interface WebserviceTypeInterface
{
	public function getType(): string;

	public function getName(): string;

	public function getDescription(): string;
}
