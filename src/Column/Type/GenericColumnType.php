<?php

namespace SyncEngine\Column\Type;

use SyncEngine\Controller\DefaultController;

class GenericColumnType implements ColumnTypeInterface
{
	const TYPE        = 'generic';
	const NAME        = 'Generic';
	const DESCRIPTION = 'Generic column';

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
