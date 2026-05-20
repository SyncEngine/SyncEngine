<?php

namespace SyncEngine\Model\Enum;

use Symfony\Component\Translation\TranslatableMessage;
use SyncEngine\Model\Enum\Interface\TranslatableEnumInterface;
use SyncEngine\Model\Enum\Trait\EnumStringTrait;

enum AutomationMode: string implements TranslatableEnumInterface
{
	use EnumStringTrait;

	// Only allow a single instance at a time. Block new requests.
	case SINGLE   = 'single';
	// Allow simultaneous requests. Do not block new requests.
	case PARALLEL = 'parallel';
	// Allow a single instance at a time but queue new requests.
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
