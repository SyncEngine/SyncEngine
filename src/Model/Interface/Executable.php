<?php

namespace App\Model\Interface;

use App\Component\ExecutionContext;

interface Executable
{
	public function execute( array $config, ExecutionContext $context, $data );
}
