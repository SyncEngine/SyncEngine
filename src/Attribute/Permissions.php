<?php

namespace SyncEngine\Attribute;

use Attribute;

/**
 * Defines field-level permissions for data operations.
 *
 * Controls which operations (read, create, edit, export) are allowed for an entity property.
 * - `create`: Field can be set when creating a new entity (e.g., on import)
 * - `edit`: Field can be modified on an existing entity (e.g., via API PATCH)
 * - `read`: Field can be retrieved/read
 * - `export`: Field can be included in exports
 *
 * Used by API controllers, exporters, importers, and other data handlers to determine field access.
 *
 * @example
 * #[Permissions(read: true, create: true, edit: true, export: true)]
 * private string $name;
 *
 * #[Permissions(read: true, create: true, edit: false, export: true)]
 * private string $ref;
 *
 * #[Permissions(read: true, create: false, edit: false, export: false)]
 * private DateTime $createdAt;
 */
#[Attribute( Attribute::TARGET_PROPERTY )]
class Permissions
{
	public function __construct(
		public readonly bool $read = true,
		public readonly bool $create = true,
		public readonly bool $edit = true,
		public readonly bool $export = true,
	) {}
}