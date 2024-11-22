<?php

namespace SyncEngine\Task\Abstract;

use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\Tag\TagParser;
use SyncEngine\Webservice\Helper\Result;

abstract class AbstractRequest extends TaskModel
{
	public function getResponseFields(): array
	{
		return [
			'param'         => [
				'label'       => $this->trans( 'Response param name' ),
				'help'        => [
					$this->trans( 'The param name where the results are located' ),
					$this->trans( 'Nested keys are supported: key.nested_key' ),
				],
				'type'        => 'text',
				'placeholder' => 'eg. products',
			],
			'key'           => [
				'label' => $this->trans( 'Key / Column name override to store results' ),
				'help'  => [
					$this->trans( 'Nested keys are supported: key.nested_key' ),
					$this->trans( 'Start with dot (.) for root' ),
				],
				'type'  => 'text', // @todo Column/Key selection field type?
			],
			'action'        => [
				'label'    => $this->trans( 'Action' ),
				'type'     => 'select',
				'default'  => 'replace',
				'required' => true,
				'choices'  => [
					'replace' => $this->trans( 'Replace current data with results' ),
					'merge'   => $this->trans( 'Merge results with current data' ),
					'insert'  => $this->trans( 'Insert results into current data without replacing existing values' ),
				],
			],
		];
	}

	public function handleResult( ?Result $result, array $config, ExecuteData $data ): ExecuteData
	{
		$key    = $config['key'] ?? null;
		$return = [];

		if ( $key ) {
			$key = ltrim( $key, $data->separator );
			if ( empty( $key ) ) {
				$key = null;
			}
		}

		if ( $result instanceof Result && $result->isSuccessful() ) {
			$return = $result->getData();
			$param  = $config['param'] ?? null;

			// @todo Use resourcedata instead of tags?
			if ( ! empty( $param ) || is_numeric( $param ) ) {
				$parser = new TagParser( (array) $return );
				$return = $parser->parseTag( $param );
			}
		}

		switch ( $config['action'] ?? '' ) {
			case 'merge':
				if ( empty( $return ) ) {
					// @todo Error?
					return $data;
				}
				if ( $key ) {
					$return = [ $key => $return ];
				}
				$data->merge( (array) $return );
			break;
			case 'insert':
				if ( empty( $return ) ) {
					// @todo Error?
					return $data;
				}
				if ( $key ) {
					$return = [ $key => $return ];
				}
				$data->insert( (array) $return );
			break;
			default:
				$data->set( $return, $key );
			break;
		}

		return $data;
	}
}
