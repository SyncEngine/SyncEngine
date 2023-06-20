<?php

namespace modules\Math\Task;

use App\Component\AutomationContext;
use App\Model\TaskModel;

class Math extends TaskModel
{
	public function __construct()
	{
		$this->type = 'math';
		$this->name = 'Math';
		$this->description = 'Simple field math';

		parent::__construct();
	}

	function getFields(): array
	{
		return [
			'method' => [
				'type' => 'select',
				'choices' => [
					'plus'=>'+',
					'minus'=>'-',
					'percentage'=>'%'
				],
			],
			'value' => [
				'type' => 'text',
			],
			'targetField' => [
				'type' => 'text',
			],
		];
	}

	function execute( array $config, AutomationContext $context, $data ): array
	{
		switch ($config['method']) {
			case "plus":
				$data = $this->plus($config, $data);
				break;
			case "minus":
				$data = $this->minus($config, $data);
				break;
			case "percentage":
				$data = $this->percentage($config, $data);
				break;
		}

		return $data;
	}

	public function plus($config, $data){
		if ( isset( $data[ $config['targetField' ] ] ) ) {
			$data[ $config['targetField' ] ] += $config['value'];
		}
		return $data;
	}
	public function minus($config, $data){
		if ( isset( $data[ $config['targetField' ] ] ) ) {
			$data[ $config['targetField' ] ] -= $config['value'];
		}
		return $data;
	}
	public function percentage($config, $data){
		if ( isset( $data[ $config['targetField' ] ] ) ) {
			$data[ $config['targetField' ] ] = $data[ $config['targetField' ] ] * ( $config['value'] / 100 );
		}
		return $data;
	}
}
