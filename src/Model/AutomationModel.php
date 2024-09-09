<?php

namespace SyncEngine\Model;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use SyncEngine\Entity\Automation;
use SyncEngine\Entity\Trace;
use SyncEngine\Model\Abstract\EngineModel;
use SyncEngine\Model\Interface\Supervisable;
use SyncEngine\Model\Interface\Taggable;
use SyncEngine\Model\Trait\Format;
use SyncEngine\Model\Trait\Supervisor;
use SyncEngine\Model\Trait\Tags;
use SyncEngine\Service\DataFormatter;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\Format\SlugFormatter;

/**
 * @extends EngineModel<Automation>
 *
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
	use Format;
	use Tags;
	use Supervisor;

	public function __construct( ?Automation $automation = null )
	{
		parent::__construct( $automation );
	}

	public function validate(): bool
	{
		$success = parent::validate();

		if ( $success ) {
			// Parse endpoint slug.
			$this->setEndpoint( $this->getEndpoint() );
		}

		return $success;
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

	public function getEventActions( string $event ): ?array
	{
		return $this->getConfig( 'events.on_' . $event, [] );
	}

	public function setEndpoint( string $endpoint ): void
	{
		$this->entity->setEndpoint(
			( new SlugFormatter( [ SlugFormatter::CASE => 'lower', SlugFormatter::SEPARATOR => '-' ] ) )->format( $endpoint )
		);
	}

	public function reset(): void
	{
		$this->setRunning( false );
		$this->setIteration( 0 );
	}

	public function isRunning(): bool
	{
		return (bool) $this->getData( 'running' );
	}

	public function setRunning( bool $running ): void
	{
		$this->setData( $running, 'running' );
	}

	public function getEventTimestamps( $state = null ): int|array
	{
		if ( ! $state ) {
			return $this->getData( 'events' );
		}

		return (int) $this->getData( 'events.' . $state, 0 );
	}

	public function setEventTimestamp( $state ): void
	{
		$this->setData( time(), 'events.' . (string) $state );
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
		$index = $this->getIteration() - 1;
		$limit = $this->getLimit();

		$offset = 0 < $index ? $index * $limit : 0;

		$this->setData( $offset, 'offset' );
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

	public function getInterval(): int
	{
		return (int) $this->getConfig( 'interval' );
	}

	public function getFields(): array
	{
		return [
			'variables' => [
				'label'       => [
					'text' => $this->trans( 'Variables' ),
					'icon' => 'variable',
				],
				'description' => $this->trans( 'Define static variables to be used within the automation.' ),
				'type'        => 'params',
				'collapsed'   => true,
				// 'taggable'    => true, @todo Support variable tags. Requirement is to filter the available tags.
			],
			'_triggers' => [
				'label'       => [
					'text' => $this->trans( 'Source' ),
					'icon' => 'database-down',
				],
				'description' => $this->trans( 'Select the data source for this automation' ),
				'fields'      => [
					'source'   => [
						'label'   => '',
						'type'    => 'switch',
						'inline'  => true,
						'choices' => [
							'request'  => [
								'text' => $this->trans( 'Request' ),
								'icon' => 'request',
							],
							'retrieve' => [
								'text' => $this->trans( 'Retrieve' ),
								'icon' => 'retrieve',
							],
						],
					],
					'request'  => [
						'label'      => [
							'text' => $this->trans( 'Request' ),
							'icon' => 'send-arrow-down',
						],
						'conditions' => [
							'source' => [ 'request' ],
						],
						'nested'     => [
							'format' => ( new DataFormatter() )->getFormatDecodeField(),
							'param'  => [
								'label' => $this->trans( 'Request param' ),
								'type'  => 'text',
							],
						],
					],
					'retrieve' => [
						'label'       => [
							'text' => $this->trans( 'Retrieve' ),
							'icon' => 'cloud-download',
						],
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
						'label'      => [
							'text' => $this->trans( 'Run automation in batches' ),
							'icon' => 'repeat',
						],
						'type'       => 'switch',
						'conditions' => [
							'source' => [ 'operator' => 'not_empty' ],
						],
						'fields'     => [
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
							'interval'     => [
								'label'      => $this->trans( 'Interval' ),
								'help'       => $this->trans( 'Set an interval time between each batch.' ),
								'type'       => 'number',
								'postfix'    => $this->trans( 'Seconds' ),
								'required'   => false,
								'conditions' => [
									'iterator' => true,
								],
							],
						],
					],
					'events'   => [
						'conditions' => [
							'source' => [ 'operator' => 'not_empty' ],
						],
						'nested'     => [
							'error_on_empty' => [
								'wrap'        => true,
								'label'       => [
									'text' => $this->trans( 'Error on empty source' ),
									'icon' => 'exclamation-triangle',
								],
								'description' => $this->trans(
									'Trigger error instead of log when there is no data available from the source triggers.'
								),
								'type'        => 'switch',
							],
						],
					],
				],
			],
			'actions'   => [
				'label'       => [
					'text' => $this->trans( 'Actions' ),
					'icon' => 'task',
				],
				'description' => $this->trans( 'The actions that need to be done with the source data.' ),
				'type'        => 'tasks',
			],
			'events'    => [
				'label'       => [
					'text' => $this->trans( 'Events' ),
					'icon' => 'event',
				],
				'description' => $this->trans( 'Select the behavior on various events in this automation.' ),
				'collapsed'   => true,
				'nested'      => [
					'on_error'   => [
						'label'       => [
							'text' => $this->trans( 'On Error' ),
						    'icon' => 'exclamation-triangle',
						],
						'description' => $this->trans( 'The actions that need to be done when the automation fails.' ),
						'collapsed'   => true,
						'type'        => 'tasks',
					],
					'on_success' => [
						'label'       => [
							'text' => $this->trans( 'On Success' ),
							'icon' => 'check-circle',
						],
						'description' => $this->trans(
							'The actions that need to be done when the automation completed successfully.'
						),
						'collapsed'   => true,
						'type'        => 'tasks',
					],
				],
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
			'variables' => '_input',
			'iterator'  => [
				'current' => '',
				'index'   => '',
				'limit'   => '',
				'offset'  => '',
			],
			'events'    => [
				'trigger' => '',
				'start'   => '',
				'stop'    => '',
				'success' => '',
				'error'   => '',
			],
		];
	}

	public function getTagsResource( $config = [], ?ExecuteContext $context = null ): array
	{
		return [
			'variables' => $this->getVariables(),
			'iterator'  => $this->getIterator(),
			'events'    => $this->getEventTimestamps(),
		];
	}

	public function getSupportedSupervisors(): array
	{
		return [
			'blueprint' => BlueprintModel::class,
		];
	}

	public static function getEntityClass(): string
	{
		return Automation::class;
	}
}
