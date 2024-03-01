<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecutionContext;
use SyncEngine\Task\Type\UtilityTaskType;

class Wait extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = UtilityTaskType::TYPE;
		$this->name        = $this->trans( 'Wait',[],"task/wait" );
		$this->description = $this->trans( 'Wait for time to pass',[],"task/wait" );
	}

	public function getFields(): array
	{
		return [
			'seconds' => [
				'label'    => $this->trans( 'Seconds to wait',[],"task/wait" ),
				'type'     => 'number',
				'required' => true,
			],
		];
	}

	public function execute( array $config, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		$seconds = $config['seconds'] ?? 0;

		if ( is_string( $seconds ) ) {
			$seconds = (float) str_replace( ',', '.', $seconds );
		}

		sleep( $seconds );

		return $data;
	}
}
