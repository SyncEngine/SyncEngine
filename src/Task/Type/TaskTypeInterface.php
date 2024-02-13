<?php

namespace SyncEngine\Task\Type;

interface TaskTypeInterface
{
	public function getType(): string;
	public function getName(): string;
	public function getDescription(): string;
}
