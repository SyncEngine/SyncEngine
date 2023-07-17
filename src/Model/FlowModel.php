<?php

namespace App\Model;

use App\Entity\Flow;
use App\Model\Interface\Exportable;
use App\Model\Trait\Config;
use App\Model\Trait\Entity;
use App\Model\Trait\Ref;

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
class FlowModel implements Exportable
{
	use Entity;
	use Config;
	use Ref;

	public function __construct( Flow $flow )
	{
		$this->entity = $flow;
		$this->config = $flow->getConfig();
	}
}
