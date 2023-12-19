<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Model\Trait\Conditionals;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecutionContext;

class Filter extends TaskModel
{
	use Conditionals;

	public function __construct()
	{
		parent::__construct();

		$this->type        = 'conditional';
		$this->name        = $this->trans( 'Filter' );
		$this->description = $this->trans( 'Remove elements based on conditionals' );
	}

	public function getFields(): array
	{
		return [
			'key'    => [
				'type'        => 'text',
				'label'       => $this->trans( 'Key / Column' ),
				'description' => $this->trans( 'Leave empty for root iteration' ),
				'help'        => $this->trans( 'Nested keys are supported: key.nested_key' ),
				'taggable'    => true,
			],
			'method'    => [
				'label'    => $this->trans( 'Filter method' ),
				'type'     => 'select',
				'default'  => 'valid',
				'required' => true,
				'choices'  => [
					'valid'   => $this->trans( 'Keep valid rows' ),
					'invalid' => $this->trans( 'Keep invalid rows' ),
				],
			],
			'conditionals' => [
				'label'    => $this->trans( 'Conditionals' ),
				'type'     => 'conditionals',
				'required' => true,
				'taggable' => true,
			],
		];
	}

	public function execute( array $config, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		if ( empty( $config['conditionals'] ) ) {
			$context->addError( $this->trans( 'No conditionals configured' ) );
		}

		$conditionals = $config['conditionals'];
		$keepValid    = 'invalid' !== $config['method'];

		foreach ( $data as $index => $row ) {
			$valid = $this->validateConditionals( $conditionals, $row );

			$valid = ( $keepValid ) ? $valid : ! $valid;

			if ( ! $valid ) {
				unset( $data[ $index ] );
			}
		}

		return $data;
	}
}
