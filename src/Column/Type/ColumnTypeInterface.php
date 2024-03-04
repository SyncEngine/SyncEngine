<?php

namespace SyncEngine\Column\Type;

interface ColumnTypeInterface
{
	public function getType(): string;
	public function getName(): string;
	public function getDescription(): string;
}
