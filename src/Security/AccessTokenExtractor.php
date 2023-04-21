<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\AccessToken\AccessTokenExtractorInterface;

class AccessTokenExtractor implements AccessTokenExtractorInterface
{
	public function extractAccessToken(Request $request): ?string
	{
		// TODO: Implement extractAccessToken() method.
		return '';
	}
}
