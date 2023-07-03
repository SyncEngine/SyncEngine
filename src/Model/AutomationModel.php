<?php

namespace App\Model;

use App\Entity\Automation;
use App\Entity\Flow;
use App\Model\Trait\Config;
use App\Model\Trait\Entity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

	public function handleRequest( Request $request ): Response
	{
		return new Response();
	}

	public function isRunning(): bool
	{
		return (bool) $this->getConfig( 'running' );
	}

	public function setRunning( bool $running )
	{
		$this->setConfig( $running, 'running' );
	}

	public function getIteration(): int
	{
		return (bool) $this->getConfig( 'current_iteration' );
	}

	public function setIteration( int $iteration )
	{
		$this->setConfig( $iteration, 'current_iteration' );
	}

	public function nextIteration()
	{
		$iteration = $this->getIteration();
		$this->setIteration( ++$iteration );
	}

	public static function getFields(): array
	{
		// @todo Implement fields.
		return [
			'source' => [
				'label' => 'Source data for this automation',
				'type' => 'switch',
				'inline' => true,
				'choices' => [
					'request' => 'Request',
					'task'    => 'Task',
					'dataset' => 'Dataset',
				],
			],
			'limit' => [
				'label' => 'Limit the number of records to fetch/run at once.',
				'type' => 'internal',
			],
		];
	}
}
