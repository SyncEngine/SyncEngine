<?php

namespace App\Model\Interface;

use App\Service\ExecutionContext;

interface Executable
{
	public function execute( array $config, ExecutionContext $context, array $data ): array;
}
