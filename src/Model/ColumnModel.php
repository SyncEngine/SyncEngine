<?php

namespace SyncEngine\Model;

use SyncEngine\Column\Type\ColumnTypeInterface;
use SyncEngine\Model\Abstract\ServiceModel;
use SyncEngine\Model\Interface\Configurable;
use SyncEngine\Model\Trait\Config;
use SyncEngine\Service\Interface\FormatInterface;

abstract class ColumnModel extends ServiceModel implements Configurable
{
	use Config;

	const SERVICE = 'Columns';

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
	abstract public function getFormatter( array $config = [] ): FormatInterface;

	public function format( $value, array $config, ?FormatInterface $source = null )
	{
		return $this->getFormatter( $config )->convert( $value, $source );
	}

	public function getFields(): array
	{
		return [];
	}

	public function normalize(): array
	{
		$props = [
			'_class'      => $this->getClassLocator(),
			'type'        => $this->getType(),
			'name'        => $this->getName(),
			'description' => $this->getDescription(),
			'fields'      => $this->getFields(),
		];

		if ( $this->isFromModule() ) {
			$props['module'] = $this->getModule()->getName();
		}

		return $props;
	}
}
