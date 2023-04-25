<?php

namespace modules\capitalization;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class capitalization extends AbstractController
{
	public function Start($config, $datafields)
	{
		$fixTag = $config["tag"];
		$datafields->$fixTag = ucwords($datafields->$fixTag);

		return $datafields;
	}

}
