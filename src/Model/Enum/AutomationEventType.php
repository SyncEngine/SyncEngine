<?php

namespace SyncEngine\Model\Enum;

use Symfony\Component\Translation\TranslatableMessage;
use SyncEngine\Model\Enum\Interface\TranslatableEnumInterface;
use SyncEngine\Model\Enum\Trait\EnumStringTrait;

enum AutomationEventType: string implements TranslatableEnumInterface
{
	use EnumStringTrait;

	case TRIGGER  = 'trigger';
	case START    = 'start';
	case STOP     = 'stop';
	case CONTINUE = 'continue';
	case CANCEL   = 'cancel';
	case SUCCESS  = 'success';
	case ERROR    = 'error';

	public static function getTranslatable( $value ): TranslatableMessage
	{
		return match ( AutomationEventType::create( $value ) ) {
			self::TRIGGER => new TranslatableMessage( 'Trigger' ),
			self::START => new TranslatableMessage( 'Start' ),
			self::STOP => new TranslatableMessage( 'Stop' ),
			self::CONTINUE => new TranslatableMessage( 'Continue' ),
			self::CANCEL => new TranslatableMessage( 'Cancel' ),
			self::SUCCESS => new TranslatableMessage( 'Success' ),
			self::ERROR => new TranslatableMessage( 'Error' ),
			default => new TranslatableMessage( 'Unknown' ),
		};
	}
}
