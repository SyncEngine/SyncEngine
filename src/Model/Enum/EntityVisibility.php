<?php

namespace SyncEngine\Model\Enum;

use Symfony\Component\Translation\TranslatableMessage;
use SyncEngine\Model\Enum\Trait\EnumStringTrait;
use SyncEngine\Model\Enum\Interface\TranslatableEnumInterface;

enum EntityVisibility: string implements TranslatableEnumInterface {
	use EnumStringTrait;

	case VISIBLE    = 'visible';
	case HIDDEN     = 'hidden';

	public static function getTranslatable( $value ): TranslatableMessage
	{
		return match ( EntityVisibility::create( $value ) ) {
			self::VISIBLE => new TranslatableMessage( 'Visible' ),
			self::HIDDEN => new TranslatableMessage( 'Hidden' ),
			default => new TranslatableMessage( 'Unknown' ),
		};
	}
}
