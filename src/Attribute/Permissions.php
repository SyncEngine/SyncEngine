<?php

namespace SyncEngine\Attribute;

use Attribute;

/**
 * Defines field-level permissions for data operations.
 *
 * Controls which operations (read, write, export) are allowed for an entity property.
 * Used by API controllers, exporters, and other data handlers to determine field access.
 *
 * @example
 * #[Permissions(read: true, write: true, export: true)]
 * private string $name;
 *
 * #[Permissions(read: true, write: false, export: false)]
 * private DateTime $createdAt;
 */
#[Attribute( Attribute::TARGET_PROPERTY )]
class Permissions
{
	public function __construct(
		public readonly bool $read = true,
		public readonly bool $write = true,
		public readonly bool $export = true,
	) {}
}