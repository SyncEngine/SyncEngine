<?php

namespace App\Service;

use App\Controller\DefaultController;
use App\Message\AutomationLooper;
use App\Model\AutomationModel;
use App\Model\FlowModel;
use App\Model\StepModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;

class ExecutePreview extends Execute
{
	public function schedule( AutomationModel $automation ): void
	{
		// Nope.
	}

	public function execute( AutomationModel $automation, ExecutionContext $context, $data = null ): array
	{
		$entityManager = DefaultController::getEntityManager();
		// @todo EntityManager in safe mode? Do now allow update queries?

		$automation->endIterator();
		$data = $this->fetch( $automation, $context, $data );

		if ( $data ) {
			$return = $data;

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

		// Do not save anything!
		$entityManager->clear();
		$errors = $context->getErrors();
		if ( $errors ) {
			return [
				'success' => false,
				'errors'  => $errors,
			];
		}

		return [
			'success' => true,
			'data'    => $return ?? [],
		];
	}

	public function executeTask( array $config, ExecutionContext $context, $data ): array
	{
		$task = $config['_class'] ?? '';
		if ( $task ) {
			if ( 'Send' === $task ) {
				return $data;
			}
			parent::executeTask( $config, $context, $data );
		}

		return $data;
	}
}
