<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Service\Data\ResourceData;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Task\Type\UtilityTaskType;

class Trace extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = UtilityTaskType::TYPE;
		$this->icon        = 'task-trace';
		$this->name        = $this->trans( 'Trace' );
		$this->description = $this->trans( 'Trace current status with a log or error.' );
	}

	public function getFields(): array
	{
		return [
			'trace'   => [
				'label'       => $this->trans( 'Trace type' ),
				'description' => $this->trans( 'Creating an error will result in the trace reporting as failed.' ),
				'type'        => 'select',
				'required'    => true,
				'choices'     => [
					'log'   => $this->trans( 'Log message' ),
					'error' => $this->trans( 'Error message' ),
				],
			],
			'message' => [
				'label'    => $this->trans( 'Message' ),
				'type'     => 'text',
				'required' => true,
			],
			'info'    => [
				'label'    => $this->trans( 'Extra info' ),
				'type'     => 'text',
				'taggable' => true,
			],
		];
	}

	public function execute( array $config, ExecuteContext $context, ExecuteData $data ): ExecuteData
	{
		if ( empty( $config['message'] ) ) {
			$context->addError( $this->trans( 'Message not configured' ) );

			return $data;
		}

		$message = $config['message'];
		$trace   = $config['trace'] ?? 'log';
		$info    = $config['info'] ?? null;

		if ( is_iterable( $info ) ) {
			$info = ResourceData::create( $info )->normalize();
		}

		if ( 'error' === $trace ) {
			$context->addError( $message, $info );
		} else {
			$context->addLog( $message, $info );
		}

		return $data;
	}
}
