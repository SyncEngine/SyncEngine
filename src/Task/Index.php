<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecutionContext;
use SyncEngine\Service\ResourceData;
use SyncEngine\Service\Tag\TagParser;

class Index extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = 'structure';
		$this->name        =  $this->trans( 'Index' );
		$this->description =  $this->trans( 'Index your data' );
	}

	public function getFields(): array
	{
		return [
			'index_key'    => [
				'label'      => $this->trans( 'New index key' ),
				'type'       => 'text',
				'help'       => $this->trans( 'The template for the indexed keys' ),
				'desc'       => $this->trans( 'Use column keys as tags: {{ column }} | Wildcards: {%key%}' ),
				// @todo Convert this to Tags (Needs big refactor in Execute service.
				'default'    => '',
				'taggable'   => true,
			],
		];
	}

	public function execute( array $config, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		if ( empty( $config['index_key'] ) ) {
			$context->addError( $this->trans( 'No index key template configured' ) );
		}

		$new = [];
		foreach ( $data as $key => $value ) {
			$new_index = ( new TagParser( [ 'data' => $value ] ) )->parseTagString( $config['index_key'] );
			$new_index = str_replace( '{%key%}', $key, $new_index );

			$new[ $new_index ] = $value;
		}

		return new ExecuteData( $new );
	}

	public function getTags(): array
	{
		return [
			'data',
		];
	}
}
