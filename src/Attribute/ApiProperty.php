<?php

namespace SyncEngine\Attribute;

use Attribute;

/**
 * Defines field exposure and access for the REST API.
 *
 * Controls which API operations can read, create, or update an entity property.
 * Follows API Platform conventions with clear semantics:
 *
 * - `readable`: Field can be read/retrieved via GET endpoints
 * - `creatable`: Field can be set when creating a new entity (POST)
 * - `updatable`: Field can be modified on an existing entity (PATCH/PUT)
 *
 * Internal system operations (imports, exports, model management) are not governed by this attribute.
 * Use #[NotExportable] to control export behavior separately.
 *
 * @example
 * #[ApiProperty(readable: true, creatable: true, updatable: true)]
 * private string $name;
 *
 * #[ApiProperty(readable: true, creatable: true, updatable: false)]
 * private string $ref;
 *
 * #[ApiProperty(readable: true, creatable: false, updatable: false)]
 * private DateTime $createdAt;
 *
 * #[ApiProperty(readable: false)]
 * private array $internalData;
 */
#[Attribute( Attribute::TARGET_PROPERTY )]
class ApiProperty
{
	public function __construct(
		public readonly bool $readable = true,
		public readonly bool $creatable = true,
		public readonly bool $updatable = true,
	) {}
}
