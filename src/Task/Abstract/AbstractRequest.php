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
			'param'  => [
				'label'       => $this->trans( 'Response param name' ),
				'help'        => [
					$this->trans( 'The param name where the results are located' ),
					$this->trans( 'Nested keys are supported: key.nested_key' ),
				],
				'type'        => 'text',
				'placeholder' => 'eg. products',
			],
			'key'    => [
				'label'       => $this->trans( 'Key / Column name override to store results' ),
				'help'        => [
					$this->trans( 'Nested keys are supported: key.nested_key' ),
					$this->trans( 'Use a dot (.) for root' ),
				],
				'type'        => 'text', // @todo Column/Key selection field type?
			],
			'action' => [
				'label'    => $this->trans( 'Action' ),
				'type'     => 'select',
				'default'  => 'replace',
				'required' => true,
				'choices'  => [
					'replace' => $this->trans( 'Replace current data with results' ),
					'merge'   => $this->trans( 'Merge results with current data' ),
				],
			],
		];
	}

	public function handleResult( ?Result $result, array $config, ExecuteData $data ): ExecuteData
	{
		$key    = $config['key'] ?? null;
		$return = [];

		if ( $key ) {
			$key = ltrim( $key, '.' );
			if ( empty( $key ) ) {
				$key = null;
			}
		}

		if ( $result instanceof Result && $result->isSuccessful() ) {
			$return = $result->getData();

			// @todo Use resourcedata instead of tags?
			if ( ! empty( $config['param'] ) || '0' === (string) ( $config['param'] ?? '' ) ) {
				$parser = new TagParser( (array) $return );
				$return = $parser->parseTag( $config['param'] );
			}
		}

		$action = $config['action'] ?? '';
		if ( 'merge' === $action ) {
			if ( empty( $return ) ) {
				// @todo Error?
				return $data;
			}
			if ( $key ) {
				$return = [ $key => $return ];
			}
			// @todo Add ResourceData method?
			$data->merge( (array) $return );
		} else {
			$data->set( $return, $key );
		}

		return $data;
	}
}
