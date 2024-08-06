<?php

namespace SyncEngine\Webservice\Type;

use SyncEngine\Controller\DefaultController;

class GenericWebserviceType implements WebserviceTypeInterface
{
	const TYPE        = 'generic';
	const NAME        = 'Generic';
	const DESCRIPTION = 'Generic webservice';

	public function getType(): string
	{
		return self::TYPE;
	}

	public function getName(): string
	{
		return DefaultController::get( 'translator' )->trans( static::NAME );
	}

	public function getDescription(): string
	{
		return DefaultController::get( 'translator' )->trans( static::DESCRIPTION );
	}
}
