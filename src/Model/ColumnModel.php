<?php

namespace SyncEngine\Model;

use SyncEngine\Column\Type\ColumnTypeInterface;
use SyncEngine\Exception\InvalidException;
use SyncEngine\Form\Fields\InputFieldType;
use SyncEngine\Form\Fields\Interface\FieldConfigInterface;
use SyncEngine\Model\Abstract\ServiceModel;
use SyncEngine\Model\Interface\Configurable;
use SyncEngine\Model\Trait\Config;
use SyncEngine\Service\Interface\FormatInterface;
use SyncEngine\Service\Provider\Columns;

abstract class ColumnModel extends ServiceModel implements Configurable
{
	use Config;

	const SERVICE = Columns::class;

	/**
	 * The type of task, can be used for categorizing.
	 *
	 * @var string|ColumnTypeInterface
	 */
	public string|ColumnTypeInterface $type = '';

	/**
	 * Human-readable name used in the interface.
	 *
	 * @var string
	 */
	public string $name = '';

	/**
	 * Human-readable description used in the interface.
	 *
	 * @var string
	 */
	public string $description = '';

	private FormatInterface $formatter;

	public function getType(): string
	{
		return $this->type instanceof ColumnTypeInterface ? $this->type->getType() : $this->type;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getDescription(): string
	{
		return $this->description;
	}
	abstract public function initFormatter( array $config = [] ): FormatInterface;

	public function setFormatter( array $config = [] ): static
	{
		$this->formatter = $this->initFormatter( $config );

		return $this;
	}

	public function getFormatter( ?array $config = null ): ?FormatInterface
	{
		if ( is_array( $config ) ) {
			return $this->initFormatter( $config );
		}

		if ( ! isset( $this->formatter ) && $this->getConfig() ) {
			$this->setFormatter( $this->getConfig() );
		}

		return $this->formatter; // Maybe: throw new \ErrorException( 'Formatter is not initialized.' );
	}

	/**
	 * @param                    $value
	 * @param  array|null        $config
	 * @param  ColumnModel|null  $source
	 *
	 * @throws InvalidException
	 * @return mixed
	 */
	public function format( $value, ?array $config = null, ?ColumnModel $source = null )
	{
		return $this->getFormatter( $config )->convert( $value, $source?->getFormatter() );
	}

	public function getFields(): array
	{
		return [];
	}

	public function getInput( array $config = [] ): ?FieldConfigInterface
	{
		$field = [
			'type' => 'text',
		];

		return new InputFieldType( $field );
	}

	public function normalize(): array
	{
		$props = [
			'_class'      => $this->getClassLocator(),
			'type'        => $this->getType(),
			'name'        => $this->getName(),
			'description' => $this->getDescription(),
			'fields'      => $this->getFields(),
			'input'       => $this->getInput(),
		];

		if ( $this->isFromModule() ) {
			$props['module'] = $this->getModule()->getName();
		}

		return $props;
	}
}
