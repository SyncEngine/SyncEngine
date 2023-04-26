<?php

namespace modules\capitalization;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class Capitalization extends AbstractController
{
	public function executeStep($config, $datafields)
	{
		$fixTag = $config["tag"];
		$datafields[ $fixTag ] = ucwords( $datafields[ $fixTag ] );

		return $datafields;
	}

}
