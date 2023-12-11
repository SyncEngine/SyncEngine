<?php

namespace SyncEngine\Model\Interface;

use SyncEngine\Service\ExecutionContext;
use SyncEngine\Service\ExecuteData;

interface Executable
{
	public function execute( array $config, ExecutionContext $context, ExecuteData $data ): ExecuteData;
}
