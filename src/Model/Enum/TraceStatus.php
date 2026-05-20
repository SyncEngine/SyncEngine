<?php

namespace SyncEngine\Model\Enum;

use Symfony\Component\Translation\TranslatableMessage;
use SyncEngine\Model\Enum\Interface\TranslatableEnumInterface;
use SyncEngine\Model\Enum\Trait\EnumStringTrait;

enum TraceStatus: string implements TranslatableEnumInterface
{
	use EnumStringTrait;

	case REGISTERED = 'registered';
	case SCHEDULED = 'scheduled';
	case QUEUED = 'queued';
	case RUNNING = 'running';
	case STOPPED = 'stopped';
	case SUCCESS = 'success';
	case FAILED = 'failed';
	case CANCELLED = 'cancelled';

	public static function getTranslatable( $value ): TranslatableMessage
	{
		return match ( TraceStatus::create( $value ) ) {
			self::REGISTERED => new TranslatableMessage( 'Registered' ),
			self::SCHEDULED => new TranslatableMessage( 'Scheduled' ),
			self::QUEUED => new TranslatableMessage( 'Queued' ),
			self::RUNNING => new TranslatableMessage( 'Running' ),
			self::STOPPED => new TranslatableMessage( 'Stopped' ),
			self::SUCCESS => new TranslatableMessage( 'Success' ),
			self::FAILED => new TranslatableMessage( 'Failed' ),
			self::CANCELLED => new TranslatableMessage( 'Cancelled' ),
			default => new TranslatableMessage( 'Unknown' ),
		};
	}
}
