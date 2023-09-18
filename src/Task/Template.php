<?php

namespace App\Task;

use App\Controller\DefaultController;
use App\Model\TaskModel;
use App\Service\ExecutionContext;
use Twig\Environment;
use Twig\Extension\StringLoaderExtension;
use Twig\Loader\FilesystemLoader;

class Template extends TaskModel
{
	public function __construct()
	{
		$this->type        = 'modifier';
		$this->name        = 'Template';
		$this->description = 'Create your own modifier template with Twig';

		parent::__construct();
	}

	public function getFields(): array
	{
		return [
			'template' => [
				'label'    => 'Template',
				'help'     => 'Output tags `{{ output }}` are parsed before the Twig engine. Only execute statements `{% function %}` are parsed in Twig.',
				'type'     => 'code',
				'taggable' => true,
				'default'  => "{# Add your code here #}\n\n",
			],
		];
	}

	function execute( array $config, ExecutionContext $context, $data )
	{
		if ( empty( $config['template'] ) ) {
			throw new \Exception( 'No template set' );
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
