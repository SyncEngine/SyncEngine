<?php

namespace App\Model;

use App\Model\Interface\Configurable;
use App\Model\Trait\Config;

class BlueprintModel implements Configurable
{
	use Config;

	/**
	 * The version of this blueprint.
	 *
	 * @var string
	 */
	public string $version = '';

	/**
	 * The type of blueprint, can be used for categorizing.
	 *
	 * @var string
	 */
	public string $type = '';

	/**
	 * The entity that this blueprint is made for.
	 *
	 * @var string
	 */
	public string $entity = '';

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

	/**
	 * Fields to configure blueprint.
	 *
	 * @var array
	 */
	public array $fields = [];

	/**
	 * Template to setup blueprint.
	 *
	 * @var array
	 */
	public array $template = [];

	public function __construct( array|string $blueprint )
	{
		if ( is_string( $blueprint ) ) {
			// @todo Load blueprint file.
		}

		$this->version     = $blueprint['version'];
		$this->type        = $blueprint['type'];
		$this->entity      = $blueprint['entity'];
		$this->name        = $blueprint['name'];
		$this->description = $blueprint['description'] ?? '';
		$this->fields      = $blueprint['fields'] ?? [];
		$this->template    = $blueprint['template'];
	}

	public function getVersion(): string
	{
		return $this->version;
	}

	public function getFields(): array
	{
		return $this->fields;
	}

	public function getTemplate(): array
	{
		return $this->template;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function getEntity(): string
	{
		return $this->entity;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getDescription(): string
	{
		return $this->description;
	}
}
