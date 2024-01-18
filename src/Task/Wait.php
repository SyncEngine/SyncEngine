<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecutionContext;

class Wait extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = 'utility';
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
