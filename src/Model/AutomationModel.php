<?php

namespace SyncEngine\Model;

use Doctrine\Common\Collections\Collection;
use SyncEngine\Entity\Automation;
use SyncEngine\Entity\Trace;
use SyncEngine\Model\Abstract\AbstractModel;
use SyncEngine\Model\Abstract\EngineModel;
use SyncEngine\Model\Interface\Supervisable;
use SyncEngine\Model\Interface\Taggable;
use SyncEngine\Model\Trait\Data;
use SyncEngine\Model\Trait\Format;
use SyncEngine\Model\Trait\Supervisor;
use SyncEngine\Model\Trait\Tags;
use SyncEngine\Service\Formatter;
use SyncEngine\Service\Slug;
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
 * @method Collection<int, Trace> getTraces()
 * @method addTrace( Trace $trace )
 * @method removeTrace( Trace $trace )
 */
class AutomationModel extends EngineModel implements Taggable, Supervisable
{
	use Data;
	use Format;
	use Tags;
	use Supervisor;

	public function __construct( ?Automation $automation = null )
	{
		parent::__construct( $automation );
	}

	public function save(): void
	{
		parent::save();

		$blueprint = $this->getConfig( '_blueprint._class' );
		if ( $blueprint ) {
			$this->setSupervisor( BlueprintModel::get( $blueprint ) );
		}

		$supervisor = $this->getSupervisor();
		if ( $supervisor instanceof BlueprintModel ) {
			$supervisor->update();
		}

		// Parse endpoint slug.
		$this->setEndpoint( $this->getEndpoint() );
	}

	public function handleRequest( Request $request ): Response
	{
		return new Response();
	}

	public function getVariables(): array
	{
		return $this->getConfig( 'variables', [] ) ?? [];
	}

	public function getActions(): array
	{
		return $this->getConfig( 'actions', [] ) ?? [];
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

	public function getOffset(): int
	{
		return (int) $this->getData( 'offset' );
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
			'index'   => $this->getIteration() - 1, // @todo implement index.
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
			'variables' => [
				'label'       => $this->trans( 'Variables' ),
				'description' => $this->trans( 'Define static variables to be used within the automation.' ),
				'type'        => 'params',
				'collapsed'   => true,
				// 'taggable'    => true, @todo Support variable tags. Requirement is to filter the available tags.
			],
			'_triggers' => [
				'label'       => $this->trans( 'Source' ),
				'description' => $this->trans( 'Select the data source for this automation' ),
				'fields'      => [
					'source'   => [
						'label'   => '',
						'type'    => 'switch',
						'inline'  => true,
						'choices' => [
							'request'  => $this->trans( 'Request' ),
							'retrieve' => $this->trans( 'Retrieve' ),
						],
					],
					'request'  => [
						'label'      => $this->trans( 'Request' ),
						'conditions' => [
							'source' => [ 'request' ],
						],
						'nested'     => [
							'format' => ( new Formatter() )->getFormatDecodeField(),
							'param'  => [
								'label' => $this->trans( 'Request param' ),
								'type'  => 'text',
							],
						],
					],
					'retrieve' => [
						'label'       => $this->trans( 'Retrieve' ),
						'description' => $this->trans( 'Configure tasks to retrieve the data' ),
						'type'        => 'tasks',
						'default'     => [
							[
								'_class' => 'Retrieve',
							],
						],
						'conditions'  => [
							'source' => [ 'retrieve' ],
						],
					],
					'iterator' => [
						'label'  => $this->trans( 'Run automation in batches' ),
						'type'   => 'switch',
						'fields' => [
							'batch_method' => [
								'label'      => $this->trans( 'Batch method' ),
								'type'       => 'select',
								'default'    => 'remote',
								'choices'    => [
									'remote' => $this->trans( 'Batches are made remotely using retrieve parameters' ),
									'local'  => $this->trans( 'Batches are created locally' ),
								],
								'conditions' => [
									'iterator' => true,
								],
							],
							'limit'        => [
								'label'      => $this->trans( 'Limit batch size' ),
								'help'       => $this->trans( 'Limit the number of records to fetch/run at once.' ),
								'type'       => 'number',
								'required'   => true,
								'conditions' => [
									'iterator' => true,
								],
								/*'fields'       => [
									'async' => [
										'label' => $this->trans( 'Run batches asynchronous' ),
										'type'  => 'checkbox',
									],
								],*/
							],
						],
					],
				],
			],
			'actions'   => [
				'label'       => $this->trans( 'Actions' ),
				'description' => $this->trans( 'The actions that need to be done with the source data.' ),
				'type'        => 'tasks',
			],
			'response'  => [
				'label'       => $this->trans( 'Response' ),
				'description' => $this->trans( 'What this automation should respond in case of a HTTP request.' ),
				'type'        => 'select',
				'choices'     => [
					''        => $this->trans( 'Success and data' ),
					'data'    => $this->trans( 'Data only' ),
					'success' => $this->trans( 'Success only' ),
				],
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

	public function supportsSupervisor( string|AbstractModel $type ): bool
	{
		if ( is_string( $type ) ) {
			return 'blueprint' === strtolower( $type );
		}

		return $type instanceof BlueprintModel;
	}

	public static function getEntityClass(): string
	{
		return Automation::class;
	}
}
