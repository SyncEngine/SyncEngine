<?php

namespace modules\EditPrice;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class EditPrice extends AbstractController
{
	public function executeStep($config, $datafields)
	{
		if(isset($config['plus'])){
			$datafields = $this->addplus($config, $datafields);
		}
		if(isset($config['minus'])){
			$datafields = $this->addminus($config, $datafields);
		}
		if(isset($config['percentage'])){
			$datafields = $this->addPercentage($config, $datafields);
		}

		return $datafields;
	}

	public function addPlus($config, $datafields){
		$datafields['price'] += $config['plus'];
		return $datafields;
	}
	public function addMinus($config, $datafields){
		$datafields['price'] -= $config['minus'];
		return $datafields;
	}
	public function addPercentage($config, $datafields){
		$datafields['price'] =  $datafields['price'] * $config['percentage'];
		return $datafields;
	}
}
