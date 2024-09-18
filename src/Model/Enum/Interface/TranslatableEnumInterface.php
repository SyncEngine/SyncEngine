<?php

namespace SyncEngine\Model\Enum\Interface;

use Symfony\Component\Translation\TranslatableMessage;

interface TranslatableEnumInterface
{
	public static function translatable( $value ): TranslatableMessage;
}
