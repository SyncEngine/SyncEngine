<?php

namespace modules\Prestashop\Task;

use App\Component\ExecutionContext;
use App\Controller\DefaultController;
use App\Service\DatasetService;
use App\Task\Retrieve;

class ps_getProducts extends Retrieve
{
	public function __construct()
	{
		parent::__construct();

		$this->type = 'prestashop';
		$this->name = 'ps_getProducts';
		$this->description = 'Get all products from Prestashop';
	}

	function getFields(): array
	{
		$parentFields = parent::getFields();

		unset( $parentFields['connection']['config']);

		return array_merge( $parentFields, [
			'format' => [
				'type' => 'select',
				'help' => 'Only for PS < 8',
				'choices' => [
					'XML'=>'XML',
					'JSON'=>'JSON',
				],
			],
			'shop_id' => [
				'help' => 'Leave blank if disabled',
				'type' => 'text',
			],
			'limit' => [
				'help' => '',
				'type' => 'text',
			],
		] );
	}

	function execute( array $config, ExecutionContext $context, $data): array
	{
		$config['connection']['endpoint'] = 'api/products';

		$config['connection']['format'] = strtolower( $config['format'] );

		// Set headers.
		$config['connection']['headers'][] = [ 'key' => 'inform', 'value' => 'niek' ];

		// Set query.
		$config['connection']['query'][] = [ 'key'=> 'shop_id', 'value' => $config['shop_id'] ?? '' ];
		$config['connection']['query'][] = [ 'key'=> 'output_format', 'value' => $config['format'] ?? '' ];

		$config['connection']['query'][] = [ 'key'=> 'display', 'value' => 'full' ];



		return parent::execute( $config, $context, $data );
	}

}
