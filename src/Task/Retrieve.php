<?php

namespace SyncEngine\Task;

use SyncEngine\Form\Fields\Collection\FieldCollection;
use SyncEngine\Model\ConnectionModel;
use SyncEngine\Model\WebserviceModel;
use SyncEngine\Runtime\ExecuteContext;
use SyncEngine\Runtime\ExecuteData;
use SyncEngine\Structure\Data\ConfigData;
use SyncEngine\Task\Abstract\AbstractRequest;
use SyncEngine\Task\Type\RequestTaskType;
use SyncEngine\Webservice\Helper\Result;

class Retrieve extends AbstractRequest
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = RequestTaskType::TYPE;
		$this->icon        = 'task-retrieve';
		$this->name        = $this->trans( 'Retrieve' );
		$this->description = $this->trans( 'Retrieve your data from your specific connection' );
	}

	function getFields(): FieldCollection
	{
		return new FieldCollection( [
			'key'         => [
				'label'    => $this->trans( 'Key / Column name' ),
				'help'        => [
					$this->trans( 'The data column name to pass to the webservice' ),
					$this->trans( 'Nested keys are supported: {example}', [ 'example' => 'key.nested_key', ] ),
					$this->trans( 'Leave empty for root' ),
				],
				'type'     => 'text', // @todo Column/Key selection field type.
				'taggable' => true,
			],
			'connection' => [
				'label'   => $this->trans( 'Connection' ),
				'type'    => 'entity',
				'entity'  => 'connection',
				'config'  => 'webservice:retrieve',
				'actions' => [ 'edit', 'create' ],
			],
			'response'   => [
				'label'  => $this->trans( 'Response data' ),
				'nested' => $this->getResponseFields(),
			],
		] );
	}

	public function execute( ConfigData $config, ExecuteContext $context, ExecuteData $data ): ExecuteData
	{
		$connectionConfig = ConfigData::create( $config->get( 'connection', [] ) );

		if ( $connectionConfig->isEmpty() ) {
			$context->addError( $this->trans( 'No connection configured' ) );

			return $data;
		}

		$result  = null;
		$key     = $config['key'] ?? null;
		$package = $data->get( $key );

		if ( $key && empty( $package ) ) {
			// Only log error if a key was defined.
			$context->addLog( $this->trans( 'Data not found: {key}', [ 'key' => $key ] ), [ 'data' => $data ] );
		} elseif ( ! is_scalar( $package ) ) {
			$package = $data->normalize( $package );
		}

		$time_start = microtime( true );

		try {
			if ( ! empty( $connectionConfig['id'] ) ) {
				$connection = ConnectionModel::get( $connectionConfig['id'] );
				$result     = $connection->handleRetrieve( $connectionConfig->normalize(), $context, $package );
			} else {
				// @todo Custom webservice without Connection?
				$webservice = WebserviceModel::get( $connectionConfig['_class'] );
				$result     = $webservice->retrieve( $connectionConfig->normalize(), $package );
			}

			/** @var Result $result */
			// Do not translate for storage.
			$context->addLog( 'Response info', $result );

		} catch ( \Throwable $e ) {
			$time_end = microtime( true );
			$info     = [
				'data'          => $package,
				'time_start'    => $time_start,
				'time_end'      => $time_end,
				'time_duration' => $time_end - $time_start,
			];
			$context->addError( $e, $info );
		}

		$responseConfig = $config['response'] ?? [];

		$return = $this->handleResult( $result, (array) $responseConfig, $data );

		// @todo Option to include in current storage?
		return ExecuteData::create( $return );
	}
}
