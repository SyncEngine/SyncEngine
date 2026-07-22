<?php

namespace SyncEngine\Controller\Api\Abstract;

use SyncEngine\Controller\DefaultController;
use SyncEngine\Controller\Interface\ApiControllerInterface;
use SyncEngine\Security\Voter\ApiTokenScopeVoter;

abstract class AbstractApiController extends DefaultController implements ApiControllerInterface
{
	protected function isGranted( mixed $attribute, mixed $subject = null ): bool
	{
		$attribute = ApiTokenScopeVoter::getScope( $attribute );

		return parent::isGranted( $attribute, $subject );
	}
}
