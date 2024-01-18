<?php

namespace SyncEngine\Model\Interface;

use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecutionContext;

interface Executable
{
	public function execute( array $config, ExecutionContext $context, ExecuteData $data ): ExecuteData;
}
