<?php

namespace App\Model;

use App\Entity\Step;
use App\Model\Trait\Config;
use App\Model\Trait\Entity;

/**
 * @method int getId()
 * @method setId( int $id )
 * @method string getName()
 * @method setName( string $name )
 * @method string getDescription()
 * @method setDescription( string $description )
 */
class StepModel
{
	use Entity;
	use Config;

	public function __construct( Step $step )
	{
		$this->entity = $step;
		$this->config = $step->getConfig();
	}
}
