<?php

namespace SyncEngine\Form\Fields\Interface;

interface FieldConfigInterface
{
	public function getName(): string;
	public function getLabel(): string;
	public function getType(): string;
	public function getDescription(): string;
	public function getHelp(): string|array;
	public function getDefaultValue();
	public function isRequired(): bool;
	public function isDisabled(): bool;
	public function isReadonly(): bool;
}
