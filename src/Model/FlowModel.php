<?php

namespace App\Model;

use App\Entity\Flow;
use App\Model\Trait\Config;
use App\Model\Trait\Entity;

/**
 * @method int getId()
 * @method setId( int $id )
 * @method string getName()
 * @method setName( string $name )
 * @method string getDescription()
 * @method setDescription( string $description )
 * @method array getSteps()
 * @method setSteps( array $flow )
 */
class FlowModel
{
	use Entity;
	use Config;

	public function __construct( Flow $flow )
	{
		$this->entity = $flow;
		$this->config = $flow->getConfig();
	}
}
