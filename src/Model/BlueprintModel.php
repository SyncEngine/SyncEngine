<?php

namespace App\Model;

use App\Model\Interface\Configurable;
use App\Model\Trait\Config;

class BlueprintModel implements Configurable
{
	use Config;

	/**
	 * The type of task, can be used for categorizing.
	 *
	 * @var string
	 */
	public string $type = '';

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

		$this->type        = $blueprint['type'] ?? '';
		$this->name        = $blueprint['name'] ?? '';
		$this->description = $blueprint['description'] ?? '';
		$this->fields      = $blueprint['fields'] ?? '';
		$this->template    = $blueprint['template'] ?? '';
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

	public function getName(): string
	{
		return $this->name;
	}

	public function getDescription(): string
	{
		return $this->description;
	}
}
