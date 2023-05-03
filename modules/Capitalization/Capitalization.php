<?php

namespace modules\Capitalization;

use App\Controller\ModuleController;

class Capitalization extends ModuleController
{
	public function executeTask($config, $datafields)
	{
		$fixTag = $config["tag"];
		$datafields[ $fixTag ] = ucwords( $datafields[ $fixTag ] );

		return $datafields;
	}

}
