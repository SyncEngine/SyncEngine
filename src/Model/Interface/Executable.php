<?php

namespace SyncEngine\Model\Interface;

use SyncEngine\Runtime\ExecuteContext;
use SyncEngine\Runtime\ExecuteData;
use SyncEngine\Structure\Data\ConfigData;

interface Executable
{
	public function execute( ConfigData $config, ExecuteContext $context, ExecuteData $data ): ExecuteData;
}
