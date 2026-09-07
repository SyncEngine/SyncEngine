<?php

namespace SyncEngine\Model\Enum\Trait;

use Symfony\Contracts\Translation\TranslatorInterface;
use SyncEngine\Model\Enum\Interface\TranslatableEnumInterface;

/**
 * @extends TranslatableEnumInterface
 * @method static getTranslatable( $value ): \Symfony\Component\Translation\TranslatableMessage
 */
trait TranslatableEnumTrait
{
	public function trans( TranslatorInterface $translator, ?string $locale = null ): string
	{
		return self::getTranslatable( $this )->trans( $translator, $locale );
	}
}
