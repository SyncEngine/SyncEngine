<?php

namespace SyncEngine\Model\Enum;

enum TraceStatus: string
{
	case SUCCESS = 'success';
	case RUNNING = 'running';
	case FAILED = 'failed';
}
