<?php

namespace modules\Capitalization\Task;

use App\Component\ExecutionContext;
use App\Model\TaskModel;

class Capitalization extends TaskModel
{
	public function __construct()
	{
		$this->type = 'capitalization';
		$this->name = 'Capitalization';
		$this->description = 'Capitalize certain words';

		parent::__construct();
	}

	function getFields(): array
	{
		return [];
	}

	function execute( array $config, ExecutionContext $context, $data ): array
	{
		$fixTag = $config['tag'];
		$data[ $fixTag ] = ucwords( $data[ $fixTag ] );

		return $data;
	}

}
