<?php

namespace App\Service;

use App\Controller\DefaultController;
use App\Entity\Automation;
use App\Entity\Flow;
use App\Entity\Step;
use App\Message\AutomationLooper;
use App\Model\AutomationModel;
use App\Model\FlowModel;
use App\Model\StepModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;

class ExecutePreview extends Execute
{
	const MODE_SAFE = 'safe';
	const MODE_LIVE = 'live';

	protected array $scope;
	protected object $testModel;
	protected array $testConfig;

	public function schedule( AutomationModel $automation ): void
	{
		// Nope.
	}

	public function preview( Request $request ): array
	{

		$scope = $request->get( 'scope' );
		if ( $scope ) {
			foreach ( $scope as $key => $entity ) {
				switch ( $entity['_entity'] ) {
					case 'Automation':
						$scope[ $key ] = AutomationModel::get( $entity['id'] );
					break;
					case 'Flow':
						$scope[ $key ] = FlowModel::get( $entity['id'] );
					break;
					case 'Step':
						$scope[ $key ] = StepModel::get( $entity['id'] );
					break;
				}
			}
		}

		/*
		$this->scope['automation'] = $scope['automation'] ? AutomationModel::get( $scope['automation'] ) : null;
		$this->scope['flow']       = $scope['flow'] ? FlowModel::get( $scope['flow'] ) : null;
		$this->scope['step']       = $scope['step'] ? StepModel::get( $scope['step'] ) : null;
		*/

		$context = new ExecutionContext( $this );

		$mode = $request->get('mode') ?? self::MODE_SAFE;
		$context->setPreviewMode( $mode );

		$ref    = $request->get('ref');
		$data   = json_decode( $request->get('data'), true );
		$config = json_decode( $request->get('config'), true );

		$this->testConfig = $config;

		switch ( $request->get('type') ) {
			case 'task':
				/*if ( $this->scope['automation'] ) {
					$return = $this->execute( $this->scope['automation'], $context, $data );
				} elseif ( $this->scope['flow'] ) {
					$return = $this->executeFlow( $this->scope['flow'], $context, $data );
				} elseif ( $this->scope['step'] ) {
					$return = $this->executeStep( $this->scope['step'], $context, $data );
				} else {
				}*/
				$return = $this->executeTask( $config, $context, $data );
				break;
			case 'step':
				$step = new StepModel( new Step() );
				$step->setConfig( $config );
				$step->setRef( $ref );

				$this->testModel = $step;

				if ( $this->scope['automation'] ) {
					$return = $this->execute( $this->scope['automation'], $context, $data );
				} elseif ( $this->scope['flow'] ) {
					$return = $this->executeFlow( $this->scope['flow'], $context, $data );
				} else {
					$return = $this->executeStep( $step, $context, $data );
				}
				break;
			case 'flow':
				$flow = new FlowModel( new Flow() );
				$flow->setConfig( $config );
				$flow->setRef( $ref );

				$this->testModel = $flow;

				if ( $this->scope['automation'] ) {
					$return = $this->execute( $this->scope['automation'], $context, $data );
				} else {
					$return = $this->executeFlow( $flow, $context, $data );
				}
				break;
			case 'automation':
				$automation = new AutomationModel( new Automation() );
				$automation->setConfig( $config );
				$automation->setRef( $ref );

				$this->testModel = $automation;

				$return = $this->execute( $automation, $context, $data );
				break;
			default:
				$context->addError( 'No preview scope set' );
				break;
		}

		$errors = $context->getErrors();
		if ( $errors ) {
			return [
				'success' => false,
				'errors'  => $errors,
			];
		}

		return [
			'success' => true,
			'data'    => [
				'Response' => $return ?? [],
				'Config' => $this->testConfig,
			],
		];
	}

	public function executeScope( ExecutionContext $context, $data = null )
	{
		// @todo Set current scope manually instead of using database entities.
	}

	public function execute( AutomationModel $automation, ExecutionContext $context, $data = null ): array
	{
		$automation->endIterator();
		$data = $this->fetch( $automation, $context, $data );

		$return = $data;
		if ( $data ) {
			$flow = FlowModel::get( $automation->getFlow() );
			if ( $flow ) {
				try {
					$return = $this->executeFlow( $flow, $context, $data );
				} catch ( \Throwable $e ) {
					$data = [];
					$context->addError( $e );
				}
			}
		} else {
			$context->addError( 'No data found' );
		}

		return $return ?? [];
	}

	public function executeTask( array $config, ExecutionContext $context, $data ): array
	{
		$task = $config['_class'] ?? '';
		if ( $task ) {
			if ( 'Send' === $task && self::MODE_LIVE !== $context->getPreviewMode() ) {
				return $data;
			}
			$data = parent::executeTask( $config, $context, $data );
		}

		return $data;
	}
}
