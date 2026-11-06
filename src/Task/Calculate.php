<?php

namespace SyncEngine\Task;

use SyncEngine\Form\Fields\Collection\FieldCollection;
use SyncEngine\Model\TaskModel;
use SyncEngine\Runtime\ExecuteContext;
use SyncEngine\Runtime\ExecuteData;
use SyncEngine\Service\Expression\MathExpressionLanguage;
use SyncEngine\Structure\Data\ConfigData;
use SyncEngine\Structure\Data\ResourceData;
use SyncEngine\Task\Type\ModifierTaskType;

class Calculate extends TaskModel
{
	private ?MathExpressionLanguage $language = null;

	public function __construct()
	{
		parent::__construct();

		$this->type        = ModifierTaskType::TYPE;
		$this->icon        = 'task-calculate';
		$this->name        = $this->trans( 'Calculate' );
		$this->description = $this->trans( 'Calculate values with math expressions' );
	}

	public function getFields(): FieldCollection
	{
		return new FieldCollection( [
			'key'          => [
				'type'     => 'text',
				'label'    => $this->trans( 'Key / Column name' ),
				'help'     => [
					$this->trans( 'Nested keys are supported: key.nested_key' ),
					$this->trans( 'Leave empty for root' ),
				],
				'taggable' => true,
			],
			'calculations' => [
				'label'       => $this->trans( 'Expressions' ),
				'description' => $this->trans(
					'Expressions can use {data}, {variables} and {cache}, e.g. {example}. Rows are calculated in order and can use earlier results.',
					[
						'{data}'      => 'data',
						'{variables}' => 'variables',
						'{cache}'     => 'cache',
						'{example}'   => 'data.total / data.quantity',
					]
				),
				'help'        => [
					$this->trans( 'Operators: + - * / % ** and comparisons; fallback with ??' ),
					$this->trans( 'Functions: {functions}', [ '{functions}' => implode( ', ', MathExpressionLanguage::FUNCTIONS ) ] ),
				],
				'type'        => 'grid',
				'required'    => true,
				'sortable'    => true,
				'columns'     => [
					'key'        => $this->trans( 'Column key/name' ),
					'expression' => $this->trans( 'Expression' ),
					'decimals'   => [
						'header' => $this->trans( 'Decimals' ),
						'type'   => 'number',
					],
				],
			],
		] );
	}

	/**
	 * @param ConfigData<array{
	 *     // Key / Column name to calculate in
	 *     key: string,
	 *     // Calculations: target key, expression and optional decimals
	 *     calculations: array<array{ key: string, expression: string, decimals?: int|string|null }>
	 * }> $config
	 * @param  ExecuteContext  $context
	 * @param  ExecuteData     $data
	 *
	 * @return ExecuteData
	 */
	public function execute( ConfigData $config, ExecuteContext $context, ExecuteData $data ): ExecuteData
	{
		$key          = $config['key'] ?? null;
		$calculations = $config['calculations'] ?? [];

		if ( empty( $calculations ) ) {
			$context->addError( $this->trans( 'No calculations configured' ) );

			return $data;
		}

		$resource = $data->get( $key );

		if ( null === $resource ) {
			$resource = [];
		} elseif ( ! is_iterable( $resource ) ) {
			$context->addError( $this->trans( 'Data is not iterable' ), [ 'key' => $key ] );

			return $data;
		}

		$resource = ResourceData::create( $resource instanceof ResourceData ? $resource->get() : $resource );

		$values = [
			'variables' => MathExpressionLanguage::toExpressionValue( $context->getVariables() ),
			'cache'     => MathExpressionLanguage::toExpressionValue( $context->getCache()->get() ),
		];

		foreach ( $calculations as $row ) {
			$target     = $row['key'] ?? '';
			$expression = trim( (string) ( $row['expression'] ?? '' ) );

			if ( '' === (string) $target || '' === $expression ) {
				continue;
			}

			try {
				$values['data'] = MathExpressionLanguage::toExpressionValue( $resource->normalize() );

				$value = $this->getMathLanguage()->evaluate( $expression, $values );

				if ( isset( $row['decimals'] ) && '' !== $row['decimals'] && is_numeric( $value ) ) {
					$value = round( (float) $value, (int) $row['decimals'] );
				}
			} catch ( \Throwable $e ) {
				$context->addError(
					$this->trans( 'Calculation failed for {key}: {message}', [ '{key}' => $target, '{message}' => $e->getMessage() ] ),
					[ 'key' => $target, 'expression' => $expression ]
				);
				continue;
			}

			$resource[ $target ] = $value;
		}

		$data->set( $resource->get(), $key );

		return $data;
	}

	private function getMathLanguage(): MathExpressionLanguage
	{
		return $this->language ??= new MathExpressionLanguage();
	}
}
