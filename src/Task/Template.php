<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecutionContext;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Template extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = 'modifier';
		$this->name        = $this->trans( 'Template' );
		$this->description = $this->trans( 'Create your own modifier template with Twig' );
	}

	public function getFields(): array
	{
		return [
			'template' => [
				'label'    => $this->trans( 'Template' ),
				'help'     => $this->trans( 'Output tags `{{ variable }}` are parsed before the Twig template engine as tags. Only execute statements `{% function %}` are parsed in Twig.' ),
				'description' => $this->trans( 'The value of `data` is used for the task output. Available variables: `data`, `config` and `context`.' ),
				'type'     => 'code',
				'taggable' => true,
				'default'  => "{# " . $this->trans( 'Add your code here' ) . " #}\n\n",
			],
		];
	}

	public function execute( array $config, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		if ( empty( $config['template'] ) ) {
			$context->addError( $this->trans( 'No template set' ) );

			return $data;
		}

		$template = $config['template'] . "{{ data|json_encode }}";

		$args = [
			'config'   => $config,
			'context'  => $context,
			'cache'    => $context->getCache(),
			'data'     => $data->get(),
		];

		try {
			$output = html_entity_decode( $this->render( $template, $config['_ref'] ?? null, $args ) );
			$output = json_decode( $output, true, 512, JSON_THROW_ON_ERROR );
		} catch ( \Throwable $e ) {
			$context->addError( $e );

			return $data;
		}

		return new ExecuteData( $output );
	}

	public function render( string $template, ?string $name, array $args = [] ): string
	{
		$twig = new Environment( new FilesystemLoader() );
		$template = $twig->createTemplate( $template, $name );
		return $template->render( $args );
	}
}
