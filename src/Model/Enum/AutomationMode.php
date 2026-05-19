<?php

namespace SyncEngine\Model\Enum;

use Symfony\Component\Translation\TranslatableMessage;
use SyncEngine\Model\Enum\Interface\TranslatableEnumInterface;
use SyncEngine\Model\Enum\Trait\EnumStringTrait;

enum AutomationMode: string implements TranslatableEnumInterface
{
	use EnumStringTrait;

	case SINGLE   = 'single';
	case PARALLEL = 'parallel';
	case QUEUED   = 'queued';

	public static function getTranslatable( $value ): TranslatableMessage
	{
		return match ( AutomationMode::create( $value ) ) {
			self::SINGLE => new TranslatableMessage( 'Single' ),
			self::PARALLEL => new TranslatableMessage( 'Parallel' ),
			self::QUEUED => new TranslatableMessage( 'Queued' ),
			default => new TranslatableMessage( 'Unknown' ),
		};
	}
}
