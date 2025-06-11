<?php

namespace SyncEngine\Model\Interface;

use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Structure\Data\ConfigData;

interface Executable
{
	public function execute( ConfigData $config, ExecuteContext $context, ExecuteData $data ): ExecuteData;
}
