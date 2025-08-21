<?php

namespace SyncEngine\Model;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use SyncEngine\Entity\Automation;
use SyncEngine\Entity\Trace;
use SyncEngine\Model\Abstract\EngineModel;
use SyncEngine\Model\Enum\AutomationEventType;
use SyncEngine\Model\Enum\TraceStatus;
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
		// @todo TaskModelCollection object.
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

	public function isScheduled(): bool
	{
		// @todo Use query/repository instead?
		return ! empty( $this->getTraces()?->findFirst( function( $id, Trace $trace ) {
			return $trace->getStatus() === TraceStatus::SCHEDULED;
		} ) );
	}

	public function getEventTimestamps(): array
	{
		return (array) $this->getData( 'events', [] );
	}

	public function getEventTimestamp( string|AutomationEventType|null $state = null ): int
	{
		return (int) $this->getData( 'events.' . AutomationEventType::create( $state )->value, 0 );
	}

	public function setEventTimestamp( string|AutomationEventType $state ): void
	{
		$state = AutomationEventType::create( $state );
		if ( $state ) {
			$this->setData( time(), 'events.' . $state->value );
		}
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

	public function enableIterator(): static
	{
		$this->setConfig( true, 'iterator' );

		return $this;
	}

	public function disableIterator(): static
	{
		$this->setConfig( false, 'iterator' );

		return $this;
	}

	public function getIterator(): array
	{
		if ( ! $this->hasIterator() ) {
			return [
				'current' => 0,
				'index'   => -1, // @todo implement index.
				'limit'   => 0,
				'offset'  => 0,
			];
		}

		// @todo DTO instead of array.
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
				'label'       => $this->trans( 'Variables' ),
				'icon'        => 'variable',
				'description' => $this->trans( 'Define static variables to be used within the automation.' ),
				'type'        => 'params',
				'collapsed'   => true,
				// 'taggable'    => true, @todo Support variable tags. Requirement is to filter the available tags.
			],
			'_triggers' => [
				'label'       => $this->trans( 'Source' ),
				'icon'        => 'database-down',
				'description' => $this->trans( 'Select the data source for this automation' ),
				'collapsed'   => true,
				'fields'      => [
					'source'   => [
						'label'   => '',
						'type'    => 'switch',
						'inline'  => true,
						'choices' => [
							'request'  => [
								'text' => $this->trans( 'Request' ),
								'icon' => 'source-request',
							],
							'retrieve' => [
								'text' => $this->trans( 'Retrieve' ),
								'icon' => 'retrieve',
							],
						],
					],
					'__spacer' => [
						'type' => 'separator',
						'size' => 1,
						'conditions' => [
							'source' => [ 'operator' => 'not_empty' ],
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
					'request'  => [
						'label'      => $this->trans( 'Request' ),
						'icon'       => 'source-request',
						'conditions' => [
							'source' => [ 'request' ],
						],
						'nested'     => [
							'format' => ( new DataFormatter() )->getFormatDecodeField(),
							'param'  => [
								'label' => $this->trans( 'Request param' ),
								'help'  => [
									$this->trans( 'Define the param name to use as the data source.' ),
									$this->trans( 'Leave empty to use the full request data.' ),
								],
								'type'  => 'text',
							],
						],
					],
					'retrieve' => [
						'label'       => $this->trans( 'Retrieve' ),
						'icon'        => 'retrieve',
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
					'events'   => [
						'conditions' => [
							'source' => [ 'operator' => 'not_empty' ],
						],
						'nested'     => [
							'error_on_empty' => [
								'wrap'        => true,
								'label'       => [
									'text' => $this->trans( 'Event handler on empty source' ),
									'icon' => 'event',
								],
								'toggle'      => [
									0 => [
										'text' => $this->trans( 'Cancel' ),
										'icon' => 'event-cancel',
									],
									1 => [
										'text' => $this->trans( 'Error' ),
										'icon' => 'event-error',
									],
								],
								'description' => $this->trans(
									'Optionally trigger error when there is no data available from the source.'
								),
								'type'        => 'switch',
							],
						],
					],
				],
			],
			'actions'   => [
				'label'       => $this->trans( 'Actions' ),
				'icon'        => 'task',
				'description' => $this->trans( 'The actions that need to be done with the source data.' ),
				'type'        => 'tasks',
				'collapsed'   => true,
			],
			'events'    => [
				'label'       => $this->trans( 'Events' ),
				'icon'        => 'event',
				'description' => $this->trans( 'Select the behavior on various events in this automation.' ),
				'collapsed'   => true,
				'nested'      => [
					'on_cancel'   => [
						'label'       => [
							'text' => $this->trans( 'On Cancel' ),
							'icon' => 'event-cancel',
						],
						'description' => $this->trans( 'Actions to execute when the automation is cancelled.' ),
						'collapsed'   => true,
						'type'        => 'tasks',
					],
					'on_error'   => [
						'label'       => [
							'text' => $this->trans( 'On Error' ),
						    'icon' => 'event-error',
						],
						'description' => $this->trans( 'Actions to execute when the automation fails.' ),
						'collapsed'   => true,
						'type'        => 'tasks',
					],
					'on_success' => [
						'label'       => [
							'text' => $this->trans( 'On Success' ),
							'icon' => 'event-success',
						],
						'description' => $this->trans(
							'Actions to execute when the automation completed successfully.'
						),
						'collapsed'   => true,
						'type'        => 'tasks',
					],
				],
			],
			'response'  => [
				'label'       => $this->trans( 'Response' ),
				'icon' 	      => 'response',
				'description' => $this->trans( 'What this automation should respond in case of a HTTP request.' ),
				'collapsed'   => true,
				'nested' => [
					'type' => [
						'label'       => $this->trans( 'Type' ),
						'type'        => 'select',
						'choices'     => [
							''         => $this->trans( 'Success and data' ),
							'data'     => $this->trans( 'Data only' ),
							'success'  => $this->trans( 'Success only' ),
							'file'     => $this->trans( 'File Download' ),
						],
					],
					'file' => [
						'nested' => [
							'key' => [
								'label'    => $this->trans( 'Download file selector' ),
								'type'     => 'text',
								'taggable' => true,
							]
						],
						'conditions' => [
							'response' => 'download',
						],
					],
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
				'trigger' => [ 'timestamp' => 0 ],
				'start'   => [ 'timestamp' => 0 ],
				'stop'    => [ 'timestamp' => 0 ],
				'success' => [ 'timestamp' => 0 ],
				'error'   => [ 'timestamp' => 0 ],
			],
		];
	}

	public function getTagsResource( $config = [], ?ExecuteContext $context = null ): array
	{
		$events = [
			'trigger' => [ 'timestamp' => 0 ],
			'start'   => [ 'timestamp' => 0 ],
			'stop'    => [ 'timestamp' => 0 ],
			'success' => [ 'timestamp' => 0 ],
			'error'   => [ 'timestamp' => 0 ],
		];

		foreach ( $this->getEventTimestamps() as $event => $timestamp ) {
			$events[ $event ]['timestamp'] = $timestamp;
		}

		return [
			'variables' => $this->getVariables(),
			'iterator'  => $this->getIterator(),
			'events'    => $events,
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
