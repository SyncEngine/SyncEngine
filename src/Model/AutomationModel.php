<?php

namespace App\Model;

use App\Entity\Automation;
use App\Entity\Flow;
use App\Model\Trait\Config;
use App\Model\Trait\Data;
use App\Model\Trait\Entity;
use App\Service\TaskService;
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
	use Data;

	public function __construct( Automation $automation )
	{
		$this->entity = $automation;
		$this->config = $automation->getConfig();
		$this->data   = $automation->getData();
	}

	public function handleRequest( Request $request ): Response
	{
		return new Response();
	}

	public function isRunning(): bool
	{
		return (bool) $this->getData( 'running' );
	}

	public function setRunning( bool $running )
	{
		$this->setData( $running, 'running' );
	}

	public function getIteration(): int
	{
		return (bool) $this->getData( 'current_iteration' );
	}

	public function setIteration( int $iteration )
	{
		$this->setData( $iteration, 'current_iteration' );
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
					'tasks'    => 'Retrieve',
					//'dataset' => 'Dataset', @todo Choice conditionals.
				],
			],
			'source_tasks' => [
				'label' => 'Retrieve',
				'type' => 'tasks',
				'default' => [
					[
						'_class' => TaskService::getTask( 'retrieve' )->getClassName(),
					]
				],
				'conditionals' => [
					'source' => [ 'tasks' ],
				],
			],
			'limit' => [
				'label' => 'Limit the number of records to fetch/run at once.',
				'type' => 'number',
			],
		];
	}
}
