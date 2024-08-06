<?php

namespace SyncEngine\Service\Trace\Enum;

enum TraceLogType: string
{
	case LOG = 'log';
	case ERROR = 'error';
	case WARNING = 'warning';
	case NOTICE = 'notice';
	case INFO = 'info';
	case DEBUG = 'debug';
	case SUCCESS = 'success';

	public static function create( int|string|TraceLogType $value ): TraceLogType
	{
		if ( $value instanceof TraceLogType ) {
			return $value;
		}

		if ( is_string( $value ) ) {
			$value = strtolower( $value );
		}

		return TraceLogType::from( $value );
	}
}
