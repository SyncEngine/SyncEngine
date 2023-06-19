<?php

namespace App\Webservice;

use App\Model\WebserviceModel;

class OAuth2 extends WebserviceModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type = 'oauth2';
		$this->name = 'OAuth 2.0';
		$this->description = 'Connect with OAuth 2.0 authorization';
	}

	public function getAuthFields(): array
	{
		return [
			'host' => [
				'label' => 'Host',
				'type' => 'text',
			],
			'header_prefix' => [
				'label' => 'Header Prefix',
				'placeholder' => 'Bearer',
				'type' => 'text',
			],
			'grant_type' => [
				'label' => 'Grant type',
				'type' => 'select',
				'choices' => [
					'auth_code' => 'Authorization Code',
					'auth_code_pkce' => 'Authorization Code with PKCE',
					'implicit' => 'Implicit',
					'pass_cred' => 'Password Credentials',
					'client_cred' => 'Client Credentials',
				]
			],
			'auth_url' => [
				'label' => 'Authorization URL',
				'type' => 'text',
				'conditionals' => [
					'grant_type' => [ 'auth_code', 'auth_code_pkce', 'implicit' ],
				]
			],
			'access_token_url' => [
				'label' => 'Token URL',
				'type' => 'text',
				'conditionals' => [
					'grant_type' => [ 'auth_code', 'auth_code_pkce', 'pass_cred', 'client_cred' ],
				]
			],
			'callback_url' => [
				'label' => 'Callback/Redirect URL',
				'type' => 'text',
				'conditionals' => [
					'grant_type' => [ 'auth_code', 'auth_code_pkce', 'implicit' ],
				]
			],
			'key' => [
				'label' => 'Client Key (ID)',
				'type' => 'text',
			],
			'secret' => [
				'label' => 'Client Secret',
				'type' => 'text',
				'conditionals' => [
					'grant_type' => [ 'auth_code', 'auth_code_pkce', 'pass_cred', 'client_cred' ],
				]
			],
			'username' => [
				'label' => 'Username',
				'type' => 'text',
				'conditionals' => [
					'grant_type' => 'pass_cred',
				]
			],
			'password' => [
				'label' => 'Password',
				'type' => 'password',
				'conditionals' => [
					'grant_type' => 'pass_cred',
				]
			],
			'scope' => [
				'label' => 'Scope',
				'type' => 'text',
				'placeholder' => 'e.g. read:org',
			],
			'state' => [
				'label' => 'State',
				'type' => 'text',
				'conditionals' => [
					'grant_type' => [ 'auth_code', 'auth_code_pkce', 'implicit' ],
				]
			],
			'client_authentication' => [
				'label' => 'Client Authentication',
				'type' => 'select',
				'choices' => [
					'basic_auth' => 'Send as Basic Auth Header',
					'body_cred' => 'Send client credentials in body',
				]
			],
			// Advanced.
			'refresh_token_url' => [
				'label' => 'Token URL',
				'type' => 'text',
			],
		];
	}

	public function getAccessToken( $config )
	{
	}

	public function getRequestUrl( array $config ): string {
		// TODO: Implement getRequestUrl() method.
	}

	public function retrieve( array $config ) {
		// TODO: Implement retrieve() method.
	}

	public function send( array $config, $data ) {
		// TODO: Implement send() method.
	}
}
