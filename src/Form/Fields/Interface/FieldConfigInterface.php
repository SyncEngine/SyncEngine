<?php

namespace SyncEngine\Form\Fields\Interface;

interface FieldConfigInterface
{
	public function getName(): string;

	public function setName( string $name ): self;

	public function getLabel(): string|array;

	public function setLabel( string|array $label ): self;

	public function generateLabel( ?string $name = null ): self;

	public function getType(): string;

	public function setType( string $type ): self;

	public function getDescription(): string;

	public function setDescription( string $description ): self;

	public function getHelp(): string|array;

	public function setHelp( string|array $help ): self;

	public function getDefaultValue();

	public function setDefaultValue( $defaultValue ): self;

	public function getConditions(): iterable; // @todo Conditions component.

	public function setConditions( iterable $conditions ): self;

	public function isRequired(): bool;

	public function setRequired( bool $required ): self;

	public function isDisabled(): bool;

	public function setDisabled( bool $disabled ): self;

	public function isReadonly(): bool;

	public function setReadonly( bool $readonly ): self;
}
