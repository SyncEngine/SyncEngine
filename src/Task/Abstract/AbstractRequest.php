<?php

namespace SyncEngine\Task\Abstract;

use SyncEngine\Model\TaskModel;
use SyncEngine\Service\Tag\TagParser;
use SyncEngine\Webservice\Helper\Result;

abstract class AbstractRequest extends TaskModel
{
	public function getResponseFields(): array
	{
		return [
			'param'  => [
				'label'       => $this->trans( 'Response param name' ),
				'help'        => $this->trans( 'The param name where the results are located' ),
				'type'        => 'text',
				'placeholder' => 'eg. products',
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
			'key'    => [
				'label'       => $this->trans( 'Data key when text is returned' ),
				'description' => $this->trans( 'Only lists/arrays are allowed in the dataflow (default: `response`)' ),
				'help'        => $this->trans( 'Nested keys are supported: key.nested_key' ),
				'type'        => 'text', // @todo Column/Key selection field type?
				'placeholder' => 'response',
				'conditions'  => [
					'action' => [ 'operator' => '!=', 'compare' => 'merge' ],
				],
			],
		];
	}

	public function handleResult( ?Result $result, $config, $data )
	{
		$return = null;

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
			// @todo Add ResourceData method?
			$return = array_merge( $data->get(), (array) $return );
		} elseif ( ! is_array( $return ) ) {
			$return = [ $config['key'] ?? 'response' => $return ];
		}

		return $return;
	}
}
