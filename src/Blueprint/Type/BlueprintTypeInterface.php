<?php

namespace SyncEngine\Blueprint\Type;

interface BlueprintTypeInterface
{
	public function getType(): string;
	public function getName(): string;
	public function getIcon(): string;
	public function getDescription(): string;
}
