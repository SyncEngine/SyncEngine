<?php

namespace App\Task;

use App\Model\ConnectionModel;
use App\Model\TaskModel;
use App\Service\ExecutionContext;
use App\Service\TagParser;
use App\Service\Webservices;

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
				'config'  => 'webservice',
				'actions' => [ 'edit', 'create' ],
			],
			'param'      => [
				'label'       => 'Response param name',
				'help'        => 'The param name where the results are located',
				'type'        => 'text',
				'placeholder' => 'eg. products',
			],
		];
	}

	function execute( array $config, ExecutionContext $context, $data )
	{
		$connectionConfig = $config['connection'];
		$result           = [];

		try {
			if ( ! empty( $connectionConfig['id'] ) ) {
				$connection = ConnectionModel::get( $connectionConfig['id'] );
				$result     = $connection->handleRetrieve( $connectionConfig );
			} else {
				// @todo Custom webservice without Connection?
				$webservice = Webservices::getWebservice( $connectionConfig['_class'] );
				$result     = $webservice->retrieve( $connectionConfig );
			}
		} catch ( \Throwable $e ) {
			$context->addError( $e );
		}

		if ( $result ) {
			if ( ! empty( $config['param'] ) || '0' === (string) ( $config['param'] ?? '' ) ) {
				$parser = new TagParser( (array) $result );
				$result = $parser->parseTag( $config['param'] );
			}

			if ( ! is_array( $result ) ) {
				$result = [ 'response' => $result ];
			}
		}

		// @todo Option to include in current dataset?
		return $result;
	}
}
