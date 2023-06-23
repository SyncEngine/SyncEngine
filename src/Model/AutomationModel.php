<?php

namespace App\Model;

use App\Entity\Automation;
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
 * @method string getEndpoint()
 * @method setEndpoint( string $endpoint )
 * @method Flow getFlow()
 * @method setFlow( Flow $flow )
 */
class AutomationModel
{
	use Entity;
	use Config;

	public function __construct( Automation $automation )
	{
		$this->entity = $automation;
		$this->config = $automation->getConfig();
	}
}
