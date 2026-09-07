<?php

namespace SyncEngine\Model\Enum;

use Symfony\Component\Translation\TranslatableMessage;
use SyncEngine\Model\Enum\Trait\EnumStringTrait;
use SyncEngine\Model\Enum\Interface\TranslatableEnumInterface;
use SyncEngine\Model\Enum\Trait\TranslatableEnumTrait;

enum EntityStatus: string implements TranslatableEnumInterface
{
	use EnumStringTrait;
	use TranslatableEnumTrait;

	case ENABLED   = 'enabled';
	case DISABLED  = 'disabled';
	case TRASHED   = 'trashed';

	public static function getTranslatable( $value ): TranslatableMessage
	{
		return match ( EntityStatus::create( $value ) ) {
			self::ENABLED => new TranslatableMessage( 'Enabled' ),
			self::DISABLED => new TranslatableMessage( 'Disabled' ),
			self::TRASHED => new TranslatableMessage( 'Trashed' ),
			default => new TranslatableMessage( 'Unknown' ),
		};
	}
}
