<?php

namespace modules\Capitalization;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class Capitalization extends AbstractController
{
	public function executeTask($config, $datafields)
	{
		$fixTag = $config["tag"];
		$datafields[ $fixTag ] = ucwords( $datafields[ $fixTag ] );

		return $datafields;
	}

}
