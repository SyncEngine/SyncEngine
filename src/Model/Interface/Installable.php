<?php

namespace SyncEngine\Model\Interface;

interface Installable
{
	public function install(): bool;
	public function uninstall(): bool;

	public function update(): bool;
}
