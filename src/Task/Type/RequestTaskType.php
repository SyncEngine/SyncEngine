<?php

namespace SyncEngine\Task\Type;

class RequestTaskType extends GenericTaskType
{
	const TYPE = 'request';
	const NAME = 'Request';
	const DESCRIPTION = 'Tasks that create requests using webservices.';
}
