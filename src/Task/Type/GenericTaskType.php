<?php

namespace SyncEngine\Task\Type;

use SyncEngine\Controller\DefaultController;

class GenericTaskType implements TaskTypeInterface
{
	const TYPE        = 'generic';
	const NAME        = 'Generic';
	const DESCRIPTION = 'Generic task';

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
