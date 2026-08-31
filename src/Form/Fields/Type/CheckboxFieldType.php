<?php

namespace SyncEngine\Form\Fields\Type;

use SyncEngine\Form\Fields\AbstractFieldType;

class CheckboxFieldType extends ToggleFieldType
{
	public function getType(): string
	{
		return 'checkbox';
	}
}
