<?php

namespace SyncEngine\Service\Interface;

interface FormatInterface
{
	public function convert( mixed $var, ?FormatInterface $fromFormat = null ): mixed;
	public function format( mixed $var ): mixed;
	public function toInt( mixed $var ): ?int;
	public function toFloat( mixed $var ): ?float;
	public function toString( mixed $var ): ?string;
	public function toBool( mixed $var ): ?bool;
	public function toArray( mixed $var ): ?array;
	public function toObject( mixed $var ): ?object;
	public function supportsFormat( FormatInterface $format ): bool;
}
