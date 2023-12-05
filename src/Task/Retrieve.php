<?php

namespace SyncEngine\Task;

use SyncEngine\Model\ConnectionModel;
use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecutionContext;
use SyncEngine\Service\TagParser;
use SyncEngine\Service\Webservices;
use SyncEngine\Webservice\Helper\Result;

class Retrieve extends TaskModel
{
	public function __construct( private readonly Webservices $webservicesService )
	{
		$this->type        = 'request';
		$this->name        = $this->trans( 'Retrieve' );
		$this->description = $this->trans( 'Retrieve your data from your specific connection' );

		parent::__construct();
	}

	function getFields(): array
	{
		return [
			'connection' => [
				'label'   => $this->trans( 'Connection' ),
				'type'    => 'entity',
				'entity'  => 'connection',
				'config'  => 'webservice:retrieve',
				'actions' => [ 'edit', 'create' ],
			],
			'param'      => [
				'label'       => $this->trans( 'Response param name' ),
				'help'        => $this->trans( 'The param name where the results are located' ),
				'type'        => 'text',
				'placeholder' => 'eg. products',
			],
			'key'        => [
				'label'       => $this->trans( 'Data key when text is returned' ),
				'description' => $this->trans( 'Only lists/arrays are allowed in the dataflow (default: `response`)' ),
				'help'        => $this->trans( 'Nested keys are supported: key.nested_key' ),
				'type'        => 'text', // @todo Column/Key selection field type?
				'placeholder' => 'response',
			],
		];
	}

	public function execute( array $config, ExecutionContext $context, array $data ): array
	{
		$connectionConfig = $config['connection'];
		$result           = null;
		$return           = [];

		try {
			if ( ! empty( $connectionConfig['id'] ) ) {
				$connection = ConnectionModel::get( $connectionConfig['id'] );
				$result     = $connection->handleRetrieve( $connectionConfig );
			} else {
				// @todo Custom webservice without Connection?
				$webservice = $this->webservicesService->getWebservice( $connectionConfig['_class'] );
				$result     = $webservice->retrieve( $connectionConfig );
			}

			$context->addLog( 'Response info for Task: ' . $config['_ref'], $result->getInfo() );

		} catch ( \Throwable $e ) {
			$context->addError( $e );
		}

		if ( $result instanceof Result && $result->isSuccessful() ) {
			$return = $result->getData();

			// @todo Use resourcedata instead of tags?
			if ( ! empty( $config['param'] ) || '0' === (string) ( $config['param'] ?? '' ) ) {
				$parser = new TagParser( (array) $return );
				$return = $parser->parseTag( $config['param'] );
			}

			if ( ! is_array( $return ) ) {
				$return = [ $config['key'] ?? 'response' => $return ];
			}
		}

		// @todo Option to include in current dataset?
		return $return;
	}
}
