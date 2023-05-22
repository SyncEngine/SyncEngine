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
		return [];
	}

	function execute(array $config, array $data): array
	{
		if(isset($config['plus'])){
			$data = $this->addplus($config, $data);
		}
		if(isset($config['minus'])){
			$data = $this->addminus($config, $data);
		}
		if(isset($config['percentage'])){
			$data = $this->addPercentage($config, $data);
		}

		return $data;
	}

	public function addPlus($config, $data){
		$data['price'] += $config['plus'];
		return $data;
	}
	public function addMinus($config, $data){
		$data['price'] -= $config['minus'];
		return $data;
	}
	public function addPercentage($config, $data){
		$data['price'] =  $data['price'] * $config['percentage'];
		return $data;
	}
}
