<?php

namespace SyncEngine\Blueprint\Type;

use SyncEngine\Controller\DefaultController;

class AbstractBlueprintType implements BlueprintTypeInterface
{
	const TYPE        = '';
	const NAME        = '';
	const ICON        = '';
	const DESCRIPTION = '';

	public function getType(): string
	{
		return static::TYPE;
	}

	public function getName(): string
	{
		return DefaultController::get('translator')->trans(static::NAME);
	}

	public function getIcon(): string
	{
		return static::ICON;
	}

	public function getDescription(): string
	{
		return DefaultController::get('translator')->trans(static::DESCRIPTION);
	}
}
