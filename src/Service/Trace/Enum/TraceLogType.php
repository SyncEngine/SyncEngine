<?php

namespace SyncEngine\Service\Trace\Enum;

use SyncEngine\Model\Enum\Trait\EnumStringTrait;

enum TraceLogType: string
{
	use EnumStringTrait;

	case LOG = 'log';
	case ERROR = 'error';
	case WARNING = 'warning';
	case NOTICE = 'notice';
	case INFO = 'info';
	case DEBUG = 'debug';
	case SUCCESS = 'success';
}
