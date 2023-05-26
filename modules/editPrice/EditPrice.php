<?php

namespace modules\EditPrice;

use App\Controller\ModuleController;
use App\Controller\TaskController;

class EditPrice extends TaskController
{
	public $type = 'editprice';
	public $name = 'EditPrice';
	public $description = 'Edit price (or number of specific fields)';

	function getFields(): array
	{
		return [
			'method' => [
				'type' => 'select',
				'choices' => ['plus'=>'+','minus'=>'-','percentage'=>'%'],
			],
			'value' => [
				'type' => 'text',
			],
			'targetField' => [
				'type' => 'text',
			],
		];
	}

	function execute(array $config, array $data): array
	{
		switch ($config['method']) {
			case "plus":
				$data = $this->addplus($config, $data);
				break;
			case "minus":
				$data = $this->addminus($config, $data);
				break;
			case "percentage":
				$data = $this->addPercentage($config, $data);
				break;
		}

		return $data;
	}

	public function loopThrough($targetField , $data){
		if(isset($data[$targetField])){
			$data[$targetField] += $config['value'];
		}else{
			foreach ($data as $datafield){
				if(is_array($datafield)){
					if(isset($datafield[$targetField])){
						$datafield[$targetField] += $config['value'];
					}else{
						foreach ($datafield as $field){
							if(is_array($field)){
								if(isset($field[$targetField])){
									$field[$targetField] += $config['value'];
								}
							}

						}
					}
				}
			}
		}
		return $data;
	}

	public function addPlus($config, $data){
		return $this->loopThrough($config['targetField'], $data);
	}
	public function addMinus($config, $data){
		//$data[$config['targetField']] -= $config['value'];
		return $this->loopThrough($config['targetField'], $data);
		return $data;
	}
	public function addPercentage($config, $data){
		//ToDo make this work
		//$data[$config['targetField']] =  $data[$config['targetField']] * $config['value'];
		return $this->loopThrough($config['targetField'], $data);
		return $data;
	}
}
