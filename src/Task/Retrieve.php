<?php

namespace App\Task;

use App\Model\ConnectionModel;
use App\Model\TaskModel;
use App\Service\ExecutionContext;
use App\Service\TagParser;
use App\Service\Webservices;
use App\Webservice\Helper\Result;

class Retrieve extends TaskModel
{
	public function __construct()
	{
		$this->type        = 'request';
		$this->name        = 'Retrieve';
		$this->description = 'Retrieve your data from your specific connection';

		parent::__construct();
	}

	function getFields(): array
	{
		return [
			'connection' => [
				'label'   => 'Connection',
				'type'    => 'entity',
				'entity'  => 'connection',
				'config'  => 'webservice:retrieve',
				'actions' => [ 'edit', 'create' ],
			],
			'param'      => [
				'label'       => 'Response param name',
				'help'        => 'The param name where the results are located',
				'type'        => 'text',
				'placeholder' => 'eg. products',
			],
			'key'        => [
				'label'       => 'Data key when text is returned',
				'description' => 'Only lists/arrays are allowed in the dataflow (default: `response`)',
				'help'        => 'Nested keys are supported: key.nested_key',
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
				$webservice = Webservices::getWebservice( $connectionConfig['_class'] );
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

			$key = $config['key'] ?? 'response';

			if ( ! is_array( $return ) ) {
				$return = [ $key => $return ];
			}
		}

		// @todo Option to include in current dataset?
		return $return;
	}
}
