<?php

namespace SyncEngine\Task;

use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecutionContext;
use Twig\Environment;
use Twig\Extension\StringLoaderExtension;
use Twig\Loader\FilesystemLoader;

class Template extends TaskModel
{
	public function __construct()
	{
		$this->type        = 'modifier';
		$this->name        = $this->trans( 'Template' );
		$this->description = $this->trans( 'Create your own modifier template with Twig' );

		parent::__construct();
	}

	public function getFields(): array
	{
		return [
			'template' => [
				'label'    => $this->trans( 'Template' ),
				'help'     => $this->trans( 'Output tags `{{ output }}` are parsed before the Twig engine. Only execute statements `{% function %}` are parsed in Twig.' ),
				'type'     => 'code',
				'taggable' => true,
				'default'  => "{# " . $this->trans( 'Add your code here' ) . " #}\n\n",
			],
		];
	}

	public function execute( array $config, ExecutionContext $context, array $data ): array
	{
		if ( empty( $config['template'] ) ) {
			$context->addError( $this->trans( 'No template set' ) );

			return $data;
		}

		$args = [
			'config'   => $config,
			'context'  => $context,
			'data'     => $data,
			'template' => $config['template'],
			'ref'      => $config['_ref'] ?? '',
		];

		$output = html_entity_decode( $this->render( $args ) );

		$data = json_decode( $output, true, 512, JSON_THROW_ON_ERROR );

		return $data;
	}

	public function render( $args ): string
	{
		$root = DefaultController::getClassFinder()->getRootDir( true );

		$twig = new Environment( new FilesystemLoader( $root . 'templates/task' ) );
		$twig->addExtension( new StringLoaderExtension() );

		$file = 'template.html.twig';

		return $twig->render( $file, $args );
	}
}
