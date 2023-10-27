<?php

namespace App\Model;

use App\Entity\Automation;
use App\Entity\Flow;
use App\Model\Interface\Configurable;
use App\Model\Interface\Exportable;
use App\Model\Interface\Persistable;
use App\Model\Interface\Taggable;
use App\Model\Trait\Config;
use App\Model\Trait\Data;
use App\Model\Trait\Entity;
use App\Model\Trait\Format;
use App\Model\Trait\Ref;
use App\Model\Trait\Tags;
use App\Service\Formatter;
use App\Service\Slug;
use App\Service\Tasks;
use Doctrine\ORM\EntityManagerInterface;
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
 */
class AutomationModel implements Exportable, Configurable, Persistable, Taggable
{
	use Entity;
	use Ref;
	use Config;
	use Data;
	use Format;
	use Tags;

	public function __construct( Automation $automation )
	{
		$this->entity = $automation;
	}

	public function parseConfig(): void
	{
		// Parse endpoint slug.
		$this->setEndpoint( $this->getEndpoint() );
	}

	public function handleRequest( Request $request ): Response
	{
		return new Response();
	}

	public function getActions(): array
	{
		return $this->getConfig( 'actions' ) ?? [];
	}

	public function setActions( array $config ): void
	{
		// @todo Task validator?
		$this->setConfig( $config, 'actions' );
	}

	public function setEndpoint( string $endpoint ): void
	{
		$this->entity->setEndpoint( ( new Slug() )->slugify( $endpoint ) );
	}

	public function isRunning(): bool
	{
		return (bool) $this->getData( 'running' );
	}

	public function setRunning( bool $running ): void
	{
		$this->setData( $running, 'running' );
	}

	public function getLimit(): int
	{
		return (int) $this->getConfig( 'limit' );
	}

	public function setLimit( int $limit ): void
	{
		$this->setConfig( $limit, 'limit' );
	}

	public function getOffset(): bool
	{
		return (bool) $this->getData( 'offset' );
	}

	public function setOffset(): void
	{
		$this->setData( $this->getIteration() * $this->getLimit(), 'offset' );
	}

	public function hasIterator(): bool
	{
		return ! empty( $this->getConfig( 'iterator' ) );
	}

	public function getIterator(): array
	{
		return [
			'current' => $this->getIteration(),
			'limit'   => $this->getLimit(),
			'offset'  => $this->getOffset(),
		];
	}

	public function getIteration(): int
	{
		return (int) $this->getData( 'iteration' );
	}

	public function setIteration( int $iteration ): void
	{
		$this->setData( $iteration, 'iteration' );

		$this->setOffset();
	}

	public function nextIteration(): void
	{
		$iteration = $this->getIteration();
		$this->setIteration( ++ $iteration );
	}

	public function endIterator(): void
	{
		$this->setIteration( 0 );
	}

	public function getFields(): array
	{
		return [
			'triggers' => [
				'label'       => 'Source',
				'description' => 'Select the data source for this automation',
				'fields'      => [
					'source'   => [
						'label'   => '',
						'type'    => 'switch',
						'inline'  => true,
						'choices' => [
							'request'  => 'Request',
							'retrieve' => 'Retrieve',
						],
					],
					'request'  => [
						'label'        => 'Request',
						'conditionals' => [
							'source' => [ 'request' ],
						],
						'nested'       => [
							'format' => ( new Formatter() )->getFormatDecodeField(),
							'param'  => [
								'label' => 'Request param',
								'type'  => 'text',
							],
						],
					],
					'retrieve' => [
						'label'        => 'Retrieve',
						'description'  => 'Configure tasks to retrieve the data',
						'type'         => 'tasks',
						'default'      => [
							[
								'_class' => Tasks::getTask( 'Retrieve' )->getClassName(),
							],
						],
						'conditionals' => [
							'source' => [ 'retrieve' ],
						],
					],
					'iterator' => [
						'label'  => 'Run automation in batches',
						'type'   => 'switch',
						'fields' => [
							'batch_method' => [
								'label'        => 'Batch method',
								'type'         => 'select',
								'default'      => 'query',
								'choices'      => [
									'query' => 'Batches are made through the query parameters',
									'local' => 'Batches are created locally',
								],
								'conditionals' => [
									'iterator' => true,
								],
							],
							'limit'        => [
								'label'        => 'Limit batch size',
								'help'         => 'Limit the number of records to fetch/run at once.',
								'type'         => 'number',
								'required'     => true,
								'conditionals' => [
									'iterator' => true,
								],
								/*'fields'       => [
									'async' => [
										'label' => 'Run batches asynchronous',
										'type'  => 'checkbox',
									],
								],*/
							],
						],
					],
				],
			],
			'actions'  => [
				'label'       => 'Actions',
				'description' => 'The actions that need to be done with the source data.',
				'type'        => 'tasks',
			],
		];
	}

	public function getTags(): array
	{
		return [
			'config' => [],
			'data'   => [
				'running',
				'iteration',
				'offset',
			],
		];
	}

	public static function getEntityClass(): string
	{
		return Automation::class;
	}
}
