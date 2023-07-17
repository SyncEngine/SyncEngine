<?php

namespace App\Model\Interface;

interface Configurable
{
	public static function getFields();

	public function getConfig();
}
