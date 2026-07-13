<?php

namespace SyncEngine\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\HttpFoundation\Request;
use SyncEngine\Controller\Admin\SystemController;
use SyncEngine\Form\Type\FormEnv;
use SyncEngine\Service\System;

/**
 * @return void
 */
#[AsCommand(
	name: 'syncengine:install',
	description: 'SyncEngine installer',
)]
class SystemInstallCommand extends Command
{
	public function __construct(
		private readonly System           $system,
		private readonly SystemController $systemController,
	) {
		parent::__construct();
	}

	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		/** @var QuestionHelper $helper */
		$helper = $this->getHelper( 'question' );

		$output->writeln( '<info>SyncEngine Installation</info>' );
		$output->writeln( '' );

		// Get current environment values
		$env = $this->system->getEnv();

		if ( $this->system->isInstalled() ) {
			$answer = $helper->ask( $input, $output, new ConfirmationQuestion( '<info>System is already installed.</info> Continue anyway? (y/n): ', false ) );

			if ( ! $answer ) {
				return Command::SUCCESS;
			}
		}

		// Get form fields from FormEnv
		$formFields = $this->systemController->formEnv( new Request(), $env )->all();
		$data       = [];

		foreach ( $formFields as $fieldName => $fieldConfig ) {
			$currentValue = $env->get( $fieldName );
			$label        = $fieldConfig['label'] ?? $fieldName;
			$required     = $fieldConfig['required'] ?? false;

			$questionText = $label;
			if ( $currentValue ) {
				$questionText .= sprintf( ' [%s]', $currentValue );
			}
			if ( $required ) {
				$questionText .= ' (required)';
			}
			$questionText .= ': ';

			$question = new Question( $questionText, $currentValue );

			if ( isset( $fieldConfig['type'] ) && $fieldConfig['type'] === 'password' ) {
				$question->setHidden( true );
				$question->setHiddenFallback( false );
			}

			$value = $helper->ask( $input, $output, $question );

			if ( $value !== null && $value !== $currentValue ) {
				$data[ $fieldName ] = $value;
			}

			// Validate required fields
			if ( $required && empty( $currentValue ) ) {
				$output->writeln( '<error>This field is required.</error>' );

				return Command::FAILURE;
			}
		}

		$this->systemController->updateEnv( $data, $env );

		$output->writeln( '' );
		$output->writeln( '<info>Processing installation...</info>' );

		try {
			// Call the install controller method with the collected data
			// @todo Fully reload ORM DB connection first?
			if ( $this->system->isInstalled() ) {
				$output->writeln( '<info>System is already installed.</info>' );

				return Command::SUCCESS;
			}

			$result = $this->system->isDatabaseConnected();

			if ( $result instanceof \Throwable ) {
				$output->writeln( '<error>Database connection error: ' . $result->getMessage() . '</error>' );

				return Command::FAILURE;
			}

			// Install database schema.
			$success = $this->system->install();

			if ( $success instanceof \Throwable ) {
				$output->writeln( '<error>Installation error: ' . $success->getMessage() . '</error>' );

				return Command::FAILURE;
			}

			// Check if installed successfully.
			$success = $this->system->isInstalled();

			if ( true === $success ) {
				$output->writeln( '<info>Installation completed successfully.</info>' );

				return Command::SUCCESS;
			} else {
				if ( $success instanceof \Throwable ) {
					$output->writeln( '<error>Installation error: ' . $success->getMessage() . '</error>' );

					return Command::FAILURE;
				}

				$output->writeln( '<error>Installation failed.</error>' );

				return Command::FAILURE;
			}
		} catch ( \Throwable $e ) {
			$output->writeln( '<error>Installation error: ' . $e->getMessage() . '</error>' );

			return Command::FAILURE;
		}
	}
}
