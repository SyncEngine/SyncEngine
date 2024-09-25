<?php

namespace SyncEngine\Model\Enum;

use Symfony\Component\Translation\TranslatableMessage;
use SyncEngine\Model\Enum\Interface\TranslatableEnumInterface;
use SyncEngine\Model\Enum\Trait\EnumStringTrait;

enum TraceStatus: string implements TranslatableEnumInterface
{
	use EnumStringTrait;

	case RUNNING = 'running';
	case STOPPED = 'stopped';
	case SUCCESS = 'success';
	case FAILED = 'failed';
	case CANCELLED = 'cancelled';

	public static function getTranslatable( $value ): TranslatableMessage
	{
		return match ( TraceStatus::create( $value ) ) {
			self::RUNNING => new TranslatableMessage( 'Running' ),
			self::STOPPED => new TranslatableMessage( 'Stopped' ),
			self::SUCCESS => new TranslatableMessage( 'Success' ),
			self::FAILED => new TranslatableMessage( 'Failed' ),
			self::CANCELLED => new TranslatableMessage( 'Cancelled' ),
			default => new TranslatableMessage( 'Unknown' ),
		};
	}
}
