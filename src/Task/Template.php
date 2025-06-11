<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Structure\Data\ConfigData;
use SyncEngine\Task\Type\ModifierTaskType;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Template extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = ModifierTaskType::TYPE;
		$this->icon        = 'task-template';
		$this->name        = $this->trans( 'Template' );
		$this->description = $this->trans( 'Create your own modifier template with Twig' );
	}

	public function getFields(): array
	{
		return [
			'template' => [
				'label'       => $this->trans( 'Template' ),
				'help'        => $this->trans(
					'Output tags {example_var} are parsed before the Twig template engine as tags. Only execute statements {example_func} are parsed in Twig.',
					[ 'example_var' => '`{{ variable }}`', 'example_func' => '`{% function %}`' ]
				),
				'description' => $this->trans(
					'The value of `data` is used for the task output. Available variables: `data`, `config`, `cache`, `variables` and `context`.'
				),
				'type'        => 'code',
				'language'    => 'jinja2', // 'twig' is not supported but Jinja2 is very similar in notation,
				'taggable'    => true,
				'default'     => "{# " . $this->trans( 'Add your code here' ) . " #}\n\n",
			],
		];
	}

	public function execute( ConfigData $config, ExecuteContext $context, ExecuteData $data ): ExecuteData
	{
		if ( empty( $config['template'] ) ) {
			$context->addError( $this->trans( 'No template set' ) );

			return $data;
		}

		$template = $config['template'] . "{{ data|json_encode }}";

		$args = [
			'config'    => $config->normalize(),
			'context'   => $context,
			'cache'     => $context->getCache(),
			'variables' => $context->getVariables(),
			'data'      => $data->normalize(),
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
		$twig     = new Environment( new FilesystemLoader() );
		$template = $twig->createTemplate( $template, $name );

		return $template->render( $args );
	}
}
