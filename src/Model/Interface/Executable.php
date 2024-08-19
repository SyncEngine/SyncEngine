<?php

namespace SyncEngine\Model\Interface;

use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecuteContext;

interface Executable
{
	public function execute( array $config, ExecuteContext $context, ExecuteData $data ): ExecuteData;
}
