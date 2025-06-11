<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Structure\Data\ConfigData;
use SyncEngine\Task\Type\UtilityTaskType;

class Wait extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = UtilityTaskType::TYPE;
		$this->icon        = 'task-wait';
		$this->name        = $this->trans( 'Wait' );
		$this->description = $this->trans( 'Wait for time to pass' );
	}

	public function getFields(): array
	{
		return [
			'seconds' => [
				'label'    => $this->trans( 'Seconds to wait' ),
				'type'     => 'number',
				'required' => true,
			],
		];
	}

	public function execute( ConfigData $config, ExecuteContext $context, ExecuteData $data ): ExecuteData
	{
		$seconds = $config['seconds'] ?? 0;

		if ( is_string( $seconds ) ) {
			$seconds = (float) str_replace( ',', '.', $seconds );
		}

		sleep( $seconds );

		return $data;
	}
}
