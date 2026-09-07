<?php

namespace SyncEngine\Model\Enum\Interface;

use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;

interface TranslatableEnumInterface extends TranslatableInterface
{
	public static function getTranslatable( $value ): TranslatableMessage;
}
