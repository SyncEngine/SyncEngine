<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\Trace\Enum\TraceLogType;
use SyncEngine\Service\Trace\TraceContext;
use SyncEngine\Service\Trace\TraceLog;
use SyncEngine\Structure\Data\ResourceData;
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
					TraceLogType::LOG->value   => $this->trans( 'Log message' ),
					TraceLogType::ERROR->value => $this->trans( 'Error message' ),
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
			'include_data'    => [
				'label'        => $this->trans( 'Include data?' ),
				'placeholder'  => $this->trans( 'Key / Column name' ),
				'help'         => [
					$this->trans( 'Nested keys are supported: key.nested_key' ),
					$this->trans( 'Leave empty for root' ),
				],
				'type'         => 'select',
				'customizable' => true,
				'choices'      => [
					''  => $this->trans( 'No' ),
					'.' => $this->trans( 'Yes' ),
				],
				'taggable'     => true,
			],
		];
	}

	public function execute( array $config, ExecuteContext $context, ExecuteData $data ): ExecuteData
	{
		if ( empty( $config['message'] ) ) {
			$context->addError( $this->trans( 'Message not configured' ) );

			return $data;
		}

		$message      = $config['message'];
		$traceType    = $config['trace'] ?? TraceLogType::LOG;
		$info         = $config['info'] ?? null;
		$dataSelector = $config['include_data'] ?? null;

		if ( is_iterable( $info ) ) {
			$info = ResourceData::create( $info )->normalize();
		}

		$trace = TraceLog::create( $message, TraceLogType::create( $traceType ), TraceContext::create( $context ) );

		if ( $dataSelector ) {
			if ( is_string( $dataSelector ) ) {
				$dataSelector = ltrim( $dataSelector, '.' );
			}
			$traceData = $data->get( $dataSelector );
			if ( is_iterable( $traceData ) ) {
				$traceData = ResourceData::create( $traceData )->normalize();
			}
			$trace->set( $traceData, 'data' );
		}

		if ( TraceLogType::ERROR === $trace->getType() ) {
			$context->addError( $trace, $info );
		} else {
			$context->addLog( $trace, $info );
		}

		return $data;
	}
}
